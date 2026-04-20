<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user_payload(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Auth User',
            'email' => 'auth@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->findOrFail($userId);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotEmpty($response->json('data.access_token'));
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_google_requires_id_token_or_access_token(): void
    {
        $this->postJson('/api/v1/auth/google', [])
            ->assertStatus(422);
    }

    public function test_forgot_password_returns_success_without_leaking_user_existence(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-registered@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_reset_password_with_valid_token_updates_password(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Reset User',
            'email' => 'reset@example.com',
            'password' => bcrypt('oldpassword123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->findOrFail($userId);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', (string) $user->getRawOriginal('password')));
    }

    public function test_reset_password_with_invalid_token_returns_422(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Reset User 2',
            'email' => 'reset2@example.com',
            'password' => bcrypt('oldpassword123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->findOrFail($userId);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
            ->assertStatus(422);
    }

    public function test_google_login_with_access_token_creates_user_and_returns_token(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'https://www.googleapis.com/oauth2/v3/userinfo') {
                return Http::response([
                    'sub' => 'google-sub-99',
                    'name' => 'Google Tester',
                    'email' => 'google_tester@example.com',
                ], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'access_token' => 'fake-google-access-token',
            'device_name' => 'phpunit-google',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'google_tester@example.com')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotEmpty($response->json('data.access_token'));

        $this->assertDatabaseHas('users', [
            'email' => 'google_tester@example.com',
            'google_id' => 'google-sub-99',
        ]);
    }

    public function test_google_login_with_id_token_returns_token(): void
    {
        config(['services.google.client_id' => 'test-google-client-id']);

        Http::fake(function ($request) {
            if (str_starts_with($request->url(), 'https://oauth2.googleapis.com/tokeninfo')) {
                return Http::response([
                    'sub' => 'google-sub-id-token',
                    'name' => 'Id Token User',
                    'email' => 'idtoken@example.com',
                    'aud' => 'test-google-client-id',
                ], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake.jwt.payload',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'idtoken@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'idtoken@example.com',
            'google_id' => 'google-sub-id-token',
        ]);
    }

    public function test_me_and_logout_work_with_sanctum_token(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Token User',
            'email' => 'token@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->findOrFail($userId);
        $token = $user->createToken('phpunit')->plainTextToken;

        $headers = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/v1/auth/me', $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id);

        $this->postJson('/api/v1/auth/logout', [], $headers)
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
