<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.portgo.api_key', 'portgo-test-key');
    }

    public function test_register_requires_api_key_header(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'João',
            'last_name' => 'Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'API key inválida.');
    }

    public function test_register_with_invalid_api_key_is_unauthorized(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'invalid-key',
        ])->postJson('/api/register', [
            'first_name' => 'João',
            'last_name' => 'Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'API key inválida.');
    }

    public function test_user_can_register_with_valid_api_key(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/register', [
            'first_name' => 'João',
            'last_name' => 'Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'uuid', 'first_name', 'last_name', 'email', 'created_at', 'updated_at'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'joao@example.com',
        ]);
    }

    public function test_user_can_reset_password_with_json_payload(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => 'old-password-123',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Senha redefinida com sucesso.');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_reset_password_does_not_accept_token_and_email_only_as_query_params(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => 'old-password-123',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/reset-password?token='.$token.'&email='.urlencode($user->email), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'email']);
    }
}
