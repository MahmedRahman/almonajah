<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /** مسار ملف log مخصص لجميع أخطاء Google OAuth لسهولة المراجعة */
    private const GOOGLE_OAUTH_LOG = 'google-oauth.log';

    private function writeGoogleOAuthLog(\Throwable $e): void
    {
        $logPath = storage_path('logs/' . self::GOOGLE_OAUTH_LOG);
        $line = str_repeat('-', 80) . "\n"
            . date('Y-m-d H:i:s') . " UTC\n"
            . "نوع الخطأ: " . get_class($e) . "\n"
            . "الرسالة: " . $e->getMessage() . "\n"
            . "الملف: " . $e->getFile() . " (سطر " . $e->getLine() . ")\n"
            . "Stack trace:\n" . $e->getTraceAsString() . "\n";
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
        return ($useHttps ? 'https://' : $request->getScheme() . '://') . $host . '/auth/google/callback';
    }

    public function redirect(Request $request)
    {
        config(['services.google.redirect' => $this->getCallbackUrl($request)]);
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            config(['services.google.redirect' => $this->getCallbackUrl($request)]);

            $googleUser = Socialite::driver('google')->user();

            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->id]);
                }
                $user->refresh();
            } else {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => null,
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]);
            }

            Auth::loginUsingId($user->id);
            $request->session()->save();

            return redirect()->route('home', ['nocache' => time()])->setStatusCode(303);
        } catch (\Throwable $e) {
            $this->writeGoogleOAuthLog($e);

            Log::error('Google OAuth Error: ' . $e->getMessage(), [
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
                $userMessage = 'خطأ في الرابط مع Google. أضف في Google Cloud Console (Authorized redirect URIs): ' . $this->getCallbackUrl($request);
            } else {
                $userMessage = "خطأ في تسجيل الدخول بـ Google:\n\n"
                    . "الرسالة: " . $fullMsg . "\n\n"
                    . "النوع: " . $type . "\n"
                    . "الملف: " . $file . " (سطر " . $lineNum . ")";
            }

            $userMessage .= "\n\n--- Stack trace (لتشخيص سبب الخطأ): ---\n" . $trace;

            return redirect()->route('home')->with('error', $userMessage);
        }
    }
}
