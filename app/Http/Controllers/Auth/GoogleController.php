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
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // البحث عن مستخدم موجود بنفس البريد الإلكتروني
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // إذا كان المستخدم موجوداً، قم بتسجيل الدخول
                // تحديث google_id إذا لم يكن موجوداً
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->id]);
                }
            } else {
                // إنشاء مستخدم جديد
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => null, // لا حاجة لكلمة مرور للمستخدمين الذين يسجلون عبر Google
                    'role' => 'user', // الدور الافتراضي
                    'email_verified_at' => now(), // Gmail emails are verified
                ]);
            }

            Auth::login($user);

            return redirect()->route('home');
        } catch (\Exception $e) {
            Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect()->route('home')->with('error', 'حدث خطأ أثناء تسجيل الدخول باستخدام Google');
        }
    }
}
