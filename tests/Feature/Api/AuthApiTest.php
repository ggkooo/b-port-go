<?php

namespace Tests\Feature\Api;

use App\Models\SchoolClass;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected int $validClassId;

    protected int $validShiftId;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.portgo.api_key', 'portgo-test-key');

        $this->validClassId = (int) SchoolClass::query()->value('id');
        $this->validShiftId = (int) Shift::query()->value('id');
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

    public function test_login_returns_profile_completed_false_when_profile_is_incomplete(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => 'password123',
            'phone' => null,
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('profile_completed', false)
            ->assertJsonStructure([
                'message',
                'uuid',
                'email',
                'profile_completed',
                'token',
            ]);
    }

    public function test_login_returns_profile_completed_true_when_profile_is_complete(): void
    {
        $user = User::factory()->create([
            'email' => 'maria@example.com',
            'password' => 'password123',
            'phone' => '11999999999',
            'state' => 'SP',
            'city' => 'São Paulo',
            'school' => 'Escola Central',
            'class' => $this->validClassId,
            'shift' => $this->validShiftId,
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('profile_completed', true)
            ->assertJsonStructure([
                'message',
                'uuid',
                'email',
                'profile_completed',
                'token',
            ]);
    }

    public function test_login_validation_errors_return_json_without_redirect(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->post('/api/login', []);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Os dados informados são inválidos.')
            ->assertJsonValidationErrors(['email', 'password']);
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

    public function test_authenticated_user_can_update_profile_with_english_parameters(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'phone' => null,
            'state' => null,
            'city' => null,
            'school' => null,
            'class' => null,
            'shift' => null,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
            'Authorization' => 'Bearer '.$token,
        ])->patchJson('/api/profile', [
            'phone' => '11999999999',
            'email' => 'joao.novo@example.com',
            'state' => 'SP',
            'city' => 'São Paulo',
            'school' => 'Escola Central',
            'class' => $this->validClassId,
            'shift' => $this->validShiftId,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Perfil atualizado com sucesso.')
            ->assertJsonPath('user.email', 'joao.novo@example.com')
            ->assertJsonPath('user.phone', '11999999999')
            ->assertJsonPath('user.class', $this->validClassId)
            ->assertJsonPath('user.shift', $this->validShiftId);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'joao.novo@example.com',
            'phone' => '11999999999',
            'state' => 'SP',
            'city' => 'São Paulo',
            'school' => 'Escola Central',
            'class' => $this->validClassId,
            'shift' => $this->validShiftId,
        ]);
    }

    public function test_authenticated_user_can_fetch_profile_without_password(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'phone' => '11999999999',
            'state' => 'SP',
            'city' => 'São Paulo',
            'school' => 'Escola Central',
            'class' => $this->validClassId,
            'shift' => $this->validShiftId,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/profile/'.$user->uuid);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'joao@example.com')
            ->assertJsonPath('user.phone', '11999999999')
            ->assertJsonPath('user.state', 'SP')
            ->assertJsonPath('user.city', 'São Paulo')
            ->assertJsonPath('user.school', 'Escola Central')
            ->assertJsonPath('user.class', $this->validClassId)
            ->assertJsonPath('user.shift', $this->validShiftId)
            ->assertJsonMissingPath('user.password');
    }

    public function test_can_list_classes_for_configuration_form(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/classes');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'classes' => [
                    ['id', 'name'],
                ],
            ])
            ->assertJsonCount(7, 'classes')
            ->assertJsonPath('classes.0.name', '6º série');
    }

    public function test_can_list_shifts_for_configuration_form(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/shifts');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'shifts' => [
                    ['id', 'name'],
                ],
            ])
            ->assertJsonCount(3, 'shifts')
            ->assertJsonPath('shifts.0.name', 'Manhã');
    }

    public function test_can_list_difficulties_for_configuration_form(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/difficulties');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'difficulties' => [
                    ['id', 'name'],
                ],
            ])
            ->assertJsonCount(3, 'difficulties')
            ->assertJsonPath('difficulties.0.name', 'Fácil')
            ->assertJsonPath('difficulties.1.name', 'Médio')
            ->assertJsonPath('difficulties.2.name', 'Difícil');
    }

    public function test_can_list_questions_for_configuration_form(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/questions');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'questions' => [
                    [
                        'id',
                        'statement',
                        'alternative_a',
                        'alternative_b',
                        'alternative_c',
                        'alternative_d',
                        'correct_alternative',
                        'tip',
                        'difficulty_id',
                        'class_id',
                        'difficulty' => ['id', 'name'],
                        'school_class' => ['id', 'name'],
                    ],
                ],
            ])
            ->assertJsonCount(525, 'questions');
    }

    public function test_can_filter_questions_by_class_and_difficulty(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/questions?class_id=1&difficulty_id=1');

        $response
            ->assertOk()
            ->assertJsonCount(25, 'questions');
    }

    public function test_can_filter_random_questions_by_class_difficulty_and_quantity(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/questions?class_id=1&difficulty_id=1&quantity=10');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'questions');

        $questions = $response->json('questions');

        $this->assertIsArray($questions);

        foreach ($questions as $question) {
            $this->assertSame(1, $question['class_id']);
            $this->assertSame(1, $question['difficulty_id']);
            $this->assertContains($question['correct_alternative'], ['a', 'b', 'c', 'd']);
        }
    }

    public function test_questions_endpoint_validates_quantity_and_filters(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/questions?class_id=999&difficulty_id=999&quantity=0');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['class_id', 'difficulty_id', 'quantity']);
    }

    public function test_profile_fetch_requires_authentication_and_does_not_redirect(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/profile/'.$user->uuid);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Não autenticado.');
    }

    public function test_profile_fetch_without_json_accept_header_still_returns_unauthorized(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->get('/api/profile/'.$user->uuid);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Não autenticado.');
    }

    public function test_profile_update_requires_required_fields(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
            'Authorization' => 'Bearer '.$token,
        ])->patchJson('/api/profile', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'phone',
                'email',
                'state',
                'city',
                'school',
                'class',
                'shift',
            ]);
    }
}
