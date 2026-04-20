<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleOAuthUserService
{
    /**
     * Find or create a user from Google profile (sub/id, name, email) — same rules as web OAuth.
     */
    public function findOrCreateFromGoogleProfile(object $googleUser): User
    {
        $email = trim((string) ($googleUser->email ?? ''));
        if ($email === '') {
            throw ValidationException::withMessages([
                'google' => ['لم يُرجع Google بريداً إلكترونياً لهذا الحساب.'],
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => (string) $googleUser->id]);
            }
            $user->refresh();

            return $user;
        }

        return User::query()->create([
            'name' => $googleUser->name ?: $email,
            'email' => $email,
            'google_id' => (string) $googleUser->id,
            'password' => null,
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Resolve profile from a Google OAuth access token (mobile / server-side).
     */
    public function profileFromAccessToken(string $accessToken): object
    {
        $accessToken = trim($accessToken);
        if ($accessToken === '') {
            throw ValidationException::withMessages([
                'access_token' => ['توكن Google غير صالح.'],
            ]);
        }

        $userResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (! $userResponse->successful()) {
            throw ValidationException::withMessages([
                'access_token' => ['فشل التحقق من Google باستخدام access_token.'],
            ]);
        }

        $userData = $userResponse->json();

        return (object) [
            'id' => (string) ($userData['sub'] ?? ''),
            'name' => (string) ($userData['name'] ?? ''),
            'email' => (string) ($userData['email'] ?? ''),
        ];
    }

    /**
     * Resolve profile from a Google ID token (Sign-In on Android/iOS) and verify audience.
     */
    public function profileFromIdToken(string $idToken): object
    {
        $idToken = trim($idToken);
        if ($idToken === '') {
            throw ValidationException::withMessages([
                'id_token' => ['توكن Google غير صالح.'],
            ]);
        }

        $expectedAud = (string) config('services.google.client_id');
        if ($expectedAud === '') {
            throw ValidationException::withMessages([
                'google' => ['إعداد GOOGLE_CLIENT_ID غير مضبوط على الخادم.'],
            ]);
        }

        $response = Http::acceptJson()->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'id_token' => ['فشل التحقق من id_token مع Google.'],
            ]);
        }

        $data = $response->json();
        $aud = (string) ($data['aud'] ?? '');
        if ($aud !== $expectedAud) {
            throw ValidationException::withMessages([
                'id_token' => ['توكن Google لا يخص هذا التطبيق (audience غير متطابق).'],
            ]);
        }

        return (object) [
            'id' => (string) ($data['sub'] ?? ''),
            'name' => (string) ($data['name'] ?? ''),
            'email' => (string) ($data['email'] ?? ''),
        ];
    }
}
