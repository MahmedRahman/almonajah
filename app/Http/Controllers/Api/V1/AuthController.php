<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\GoogleOAuthUserService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة'],
            ]);
        }

        $deviceName = trim((string) ($credentials['device_name'] ?? $request->userAgent() ?? 'mobile-app'));
        if ($deviceName === '') {
            $deviceName = 'mobile-app';
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token_type' => 'Bearer',
            'access_token' => $token,
        ]);
    }

    /**
     * تسجيل الدخول عبر Google للتطبيقات (يرسل id_token من Google Sign-In أو access_token من OAuth).
     */
    public function google(Request $request, GoogleOAuthUserService $googleOAuth)
    {
        $data = $request->validate([
            'id_token' => ['nullable', 'string'],
            'access_token' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $idToken = trim((string) ($data['id_token'] ?? ''));
        $accessToken = trim((string) ($data['access_token'] ?? ''));

        if ($idToken === '' && $accessToken === '') {
            throw ValidationException::withMessages([
                'id_token' => ['يجب إرسال id_token أو access_token من Google.'],
            ]);
        }

        $profile = $idToken !== ''
            ? $googleOAuth->profileFromIdToken($idToken)
            : $googleOAuth->profileFromAccessToken($accessToken);

        $user = $googleOAuth->findOrCreateFromGoogleProfile($profile);

        $deviceName = trim((string) ($data['device_name'] ?? $request->userAgent() ?? 'mobile-app'));
        if ($deviceName === '') {
            $deviceName = 'mobile-app';
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token_type' => 'Bearer',
            'access_token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return $this->successResponse([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * طلب رابط إعادة تعيين كلمة المرور (يُرسل بريد إذا كان الحساب موجوداً).
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        return $this->successResponse([
            'message' => 'إذا كان البريد مسجّلاً لدينا، ستصلك رسالة تحتوي على رابط إعادة تعيين كلمة المرور.',
        ]);
    }

    /**
     * إعادة تعيين كلمة المرور باستخدام التوكن المرسل بالبريد (نفس التوكن المستخدم في صفحة الويب).
     */
    public function resetPassword(Request $request)
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
            return $this->successResponse([
                'message' => 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [$this->passwordResetErrorMessage($status)],
        ]);
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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->successResponse([
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }
}
