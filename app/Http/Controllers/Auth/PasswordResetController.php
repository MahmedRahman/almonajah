<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'إذا كان البريد مسجّلاً لدينا، ستصلك رسالة تحتوي على رابط إعادة التعيين.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPasswordWeb(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.');
        }

        return back()->withErrors([
            'email' => [$this->passwordResetErrorMessage($status)],
        ])->withInput();
    }

    private function passwordResetErrorMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'رابط إعادة التعيين غير صالح أو منتهي.',
            Password::INVALID_USER => 'لم نجد حساباً بهذا البريد.',
            Password::RESET_THROTTLED => 'محاولات كثيرة. حاول لاحقاً.',
            default => 'تعذّر إعادة تعيين كلمة المرور.',
        };
    }
}
