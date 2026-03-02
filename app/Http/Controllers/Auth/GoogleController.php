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

            // البحث عن مستخدم موجود بنفس البريد الإلكتروني
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // مستخدم موجود: تحديث google_id إن لزم ثم تحديث الموديل من DB
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->id]);
                }
                $user->refresh();
            } else {
                // إنشاء مستخدم جديد
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => null,
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]);
            }

            // تسجيل الدخول بالـ ID لضمان تحميل المستخدم من DB واتساق الجلسة
            Auth::loginUsingId($user->id);
            $request->session()->save();

            // 303 See Other + nocache لضمان أن المتصفح يطلب الصفحة من السيرفر ولا يعيد استخدام الكاش
            return redirect()->route('home', ['nocache' => time()])->setStatusCode(303);
        } catch (\Exception $e) {
            $userMessage = 'حدث خطأ أثناء تسجيل الدخول باستخدام Google.';
            if (str_contains($e->getMessage(), 'redirect_uri_mismatch')) {
                $userMessage = 'خطأ في إعدادات الرابط مع Google. يرجى إضافة الرابط التالي في Google Cloud Console (Authorized redirect URIs): ' . $this->getCallbackUrl($request);
            }

            Log::error('Google OAuth Error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'user_message' => $userMessage,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('home')->with('error', $userMessage);
        }
    }
}
