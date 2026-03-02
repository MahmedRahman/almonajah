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
            $msg = trim($e->getMessage());
            $msg = str_replace(["\r", "\n"], ' ', $msg);
            if (strlen($msg) > 300) {
                $msg = substr($msg, 0, 300) . '…';
            }
            $type = get_class($e);
            $file = $e->getFile();
            $line = $e->getLine();

            Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
                'exception' => $type,
                'file' => $file,
                'line' => $line,
                'trace' => $e->getTraceAsString(),
            ]);

            if (str_contains((string) $e->getMessage(), 'redirect_uri_mismatch')) {
                $userMessage = 'خطأ في الرابط مع Google. أضف في Google Cloud Console (Authorized redirect URIs): ' . $this->getCallbackUrl($request);
            } else {
                $userMessage = 'خطأ في تسجيل الدخول بـ Google:' . "\n" . $msg . "\n" . '(' . $type . ' في ' . basename($file) . ' سطر ' . $line . ')';
            }

            return redirect()->route('home')->with('error', $userMessage);
        }
    }
}
