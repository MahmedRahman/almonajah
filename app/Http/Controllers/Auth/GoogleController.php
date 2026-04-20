<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\GoogleOAuthUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /** مسار ملف log مخصص لجميع أخطاء Google OAuth لسهولة المراجعة */
    private const GOOGLE_OAUTH_LOG = 'google-oauth.log';

    private function writeGoogleOAuthLog(\Throwable $e): void
    {
        $logPath = storage_path('logs/'.self::GOOGLE_OAUTH_LOG);
        $line = str_repeat('-', 80)."\n"
            .date('Y-m-d H:i:s')." UTC\n"
            .'نوع الخطأ: '.get_class($e)."\n"
            .'الرسالة: '.$e->getMessage()."\n"
            .'الملف: '.$e->getFile().' (سطر '.$e->getLine().")\n"
            ."Stack trace:\n".$e->getTraceAsString()."\n";
        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Build the OAuth callback URL so it matches exactly what is in Google Cloud Console.
     * Uses HTTPS when APP_URL is HTTPS (e.g. production behind proxy).
     */
    private function getCallbackUrl(Request $request): string
    {
        $base = config('app.url');
        $useHttps = str_starts_with($base, 'https://');
        $host = $request->getHttpHost();

        return ($useHttps ? 'https://' : $request->getScheme().'://').$host.'/auth/google/callback';
    }

    public function redirect(Request $request)
    {
        config(['services.google.redirect' => $this->getCallbackUrl($request)]);

        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * تبادل code مع Google يدوياً بدون الاعتماد على الجلسة (state) لحل InvalidStateException.
     */
    private function getGoogleUserFromCode(Request $request): object
    {
        $code = $request->input('code');
        if (! $code) {
            throw new \RuntimeException('لم يُرسل رمز التفعيل من Google.');
        }
        $redirectUri = $this->getCallbackUrl($request);
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        if (! $clientId || ! $clientSecret) {
            throw new \RuntimeException('إعدادات Google OAuth غير مكتملة (client_id أو client_secret).');
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResponse->successful()) {
            $body = $tokenResponse->json();
            $err = $body['error_description'] ?? $body['error'] ?? $tokenResponse->body();
            throw new \RuntimeException('فشل استلام التوكن من Google: '.(is_string($err) ? $err : json_encode($err)));
        }

        $data = $tokenResponse->json();
        $accessToken = $data['access_token'] ?? null;
        if (! $accessToken) {
            throw new \RuntimeException('لم يُرجع Google مفتاح وصول.');
        }

        $userResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
        if (! $userResponse->successful()) {
            throw new \RuntimeException('فشل جلب بيانات المستخدم من Google.');
        }

        $userData = $userResponse->json();

        return (object) [
            'id' => $userData['sub'] ?? '',
            'name' => $userData['name'] ?? '',
            'email' => $userData['email'] ?? '',
        ];
    }

    public function callback(Request $request)
    {
        try {
            config(['services.google.redirect' => $this->getCallbackUrl($request)]);

            try {
                $googleUser = Socialite::driver('google')->stateless()->user();
            } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
                $googleUser = $this->getGoogleUserFromCode($request);
            }

            $profile = is_object($googleUser) && method_exists($googleUser, 'getId')
                ? (object) [
                    'id' => $googleUser->getId(),
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                ]
                : (object) [
                    'id' => $googleUser->id ?? '',
                    'name' => $googleUser->name ?? '',
                    'email' => $googleUser->email ?? '',
                ];

            $user = app(GoogleOAuthUserService::class)->findOrCreateFromGoogleProfile($profile);

            Auth::loginUsingId($user->id);
            $request->session()->save();

            return redirect()->route('home', ['nocache' => time()])->setStatusCode(303);
        } catch (\Throwable $e) {
            $this->writeGoogleOAuthLog($e);

            Log::error('Google OAuth Error: '.$e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $fullMsg = $e->getMessage();
            $type = get_class($e);
            $file = $e->getFile();
            $lineNum = $e->getLine();
            $trace = $e->getTraceAsString();

            if (str_contains((string) $fullMsg, 'redirect_uri_mismatch')) {
                $userMessage = 'خطأ في الرابط مع Google. أضف في Google Cloud Console (Authorized redirect URIs): '.$this->getCallbackUrl($request);
            } else {
                $userMessage = "خطأ في تسجيل الدخول بـ Google:\n\n"
                    .'الرسالة: '.$fullMsg."\n\n"
                    .'النوع: '.$type."\n"
                    .'الملف: '.$file.' (سطر '.$lineNum.')';
            }

            $userMessage .= "\n\n--- Stack trace (لتشخيص سبب الخطأ): ---\n".$trace;

            return redirect()->route('home')->with('error', $userMessage);
        }
    }
}
