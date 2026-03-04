<?php

namespace Tests\Feature\Api;

use App\Models\ActivityType;
use App\Models\Question;
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
                'user' => ['id', 'uuid', 'first_name', 'last_name', 'email', 'is_admin', 'created_at', 'updated_at'],
            ]);

        $response->assertJsonPath('user.is_admin', true);

        $this->assertDatabaseHas('users', [
            'email' => 'joao@example.com',
            'is_admin' => true,
        ]);
    }

    public function test_second_registered_user_is_not_admin(): void
    {
        $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/register', [
            'first_name' => 'Primeiro',
            'last_name' => 'Usuario',
            'email' => 'primeiro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/register', [
            'first_name' => 'Segundo',
            'last_name' => 'Usuario',
            'email' => 'segundo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.is_admin', false);

        $this->assertDatabaseHas('users', [
            'email' => 'primeiro@example.com',
            'is_admin' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'segundo@example.com',
            'is_admin' => false,
        ]);
    }

    public function test_login_returns_profile_completed_false_when_profile_is_incomplete(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => 'password123',
            'phone' => null,
            'is_admin' => false,
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('is_admin', false)
            ->assertJsonPath('profile_completed', false)
            ->assertJsonStructure([
                'message',
                'uuid',
                'email',
                'is_admin',
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
            'is_admin' => true,
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('is_admin', true)
            ->assertJsonPath('profile_completed', true)
            ->assertJsonStructure([
                'message',
                'uuid',
                'email',
                'is_admin',
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
        $this->createQuestion();

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
                        'activity_type_id',
                        'difficulty' => ['id', 'name'],
                        'school_class' => ['id', 'name'],
                        'activity_type' => ['id', 'name', 'slug'],
                    ],
                ],
            ]);

        $this->assertIsArray($response->json('questions'));
    }

    public function test_can_list_activity_types_for_configuration_form(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/activity-types');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'activity_types' => [
                    ['id', 'name', 'slug'],
                ],
            ])
            ->assertJsonCount(2, 'activity_types')
            ->assertJsonPath('activity_types.0.slug', 'gramatica')
            ->assertJsonPath('activity_types.1.slug', 'interpretacao-textual');
    }

    public function test_can_filter_questions_by_class_and_difficulty(): void
    {
        $this->createQuestion(classId: 1, difficultyId: 1);
        $this->createQuestion(classId: 2, difficultyId: 1);

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/questions?class_id=1&difficulty_id=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'questions');
    }

    public function test_can_filter_random_questions_by_class_difficulty_and_quantity(): void
    {
        foreach (range(1, 12) as $index) {
            $this->createQuestion(
                classId: 1,
                difficultyId: 1,
                statement: 'Questão de teste '.$index
            );
        }

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

    public function test_can_filter_questions_by_activity_type(): void
    {
        $activityTypeId = (int) ActivityType::query()
            ->where('slug', 'gramatica')
            ->value('id');

        $otherActivityTypeId = (int) ActivityType::query()
            ->where('slug', 'interpretacao-textual')
            ->value('id');

        $this->createQuestion(classId: 1, difficultyId: 1, activityTypeId: $activityTypeId);
        $this->createQuestion(classId: 1, difficultyId: 1, activityTypeId: $otherActivityTypeId);

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/questions?class_id=1&difficulty_id=1&activity_type_id='.$activityTypeId);

        $response->assertOk();

        $questions = $response->json('questions');

        $this->assertIsArray($questions);
        $this->assertNotEmpty($questions);

        foreach ($questions as $question) {
            $this->assertSame(1, $question['class_id']);
            $this->assertSame(1, $question['difficulty_id']);
            $this->assertSame($activityTypeId, $question['activity_type_id']);
            $this->assertSame('gramatica', $question['activity_type']['slug']);
        }
    }

    public function test_can_create_question_with_valid_payload(): void
    {
        $adminUser = User::factory()->create([
            'is_admin' => true,
        ]);
        $token = $adminUser->createToken('api-token')->plainTextToken;

        $activityTypeId = (int) ActivityType::query()->value('id');

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/questions', [
            'statement' => 'Qual é a função sintática do termo destacado?',
            'alternative_a' => 'Sujeito',
            'alternative_b' => 'Objeto direto',
            'alternative_c' => 'Predicado',
            'alternative_d' => 'Adjunto adnominal',
            'correct_alternative' => 'b',
            'tip' => 'Analise o verbo e pergunte quem ou o quê sofre a ação.',
            'difficulty_id' => 1,
            'class_id' => 1,
            'activity_type_id' => $activityTypeId,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Questão criada com sucesso.')
            ->assertJsonPath('question.statement', 'Qual é a função sintática do termo destacado?')
            ->assertJsonPath('question.correct_alternative', 'b')
            ->assertJsonPath('question.difficulty_id', 1)
            ->assertJsonPath('question.class_id', 1)
            ->assertJsonPath('question.activity_type_id', $activityTypeId)
            ->assertJsonStructure([
                'message',
                'question' => [
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
                    'activity_type_id',
                    'difficulty' => ['id', 'name'],
                    'school_class' => ['id', 'name'],
                    'activity_type' => ['id', 'name', 'slug'],
                ],
            ]);

        $this->assertDatabaseHas('questions', [
            'statement' => 'Qual é a função sintática do termo destacado?',
            'correct_alternative' => 'b',
            'difficulty_id' => 1,
            'class_id' => 1,
            'activity_type_id' => $activityTypeId,
        ]);
    }

    public function test_create_question_validates_required_fields(): void
    {
        $adminUser = User::factory()->create([
            'is_admin' => true,
        ]);
        $token = $adminUser->createToken('api-token')->plainTextToken;

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/questions', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'statement',
                'alternative_a',
                'alternative_b',
                'alternative_c',
                'alternative_d',
                'correct_alternative',
                'tip',
                'difficulty_id',
                'class_id',
                'activity_type_id',
            ]);
    }

    public function test_create_question_requires_authentication(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->postJson('/api/questions', [
            'statement' => 'Questão sem autenticação',
            'alternative_a' => 'A',
            'alternative_b' => 'B',
            'alternative_c' => 'C',
            'alternative_d' => 'D',
            'correct_alternative' => 'a',
            'tip' => 'Dica',
            'difficulty_id' => 1,
            'class_id' => 1,
            'activity_type_id' => (int) ActivityType::query()->value('id'),
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Não autenticado.');
    }

    public function test_create_question_requires_admin_user(): void
    {
        $regularUser = User::factory()->create([
            'is_admin' => false,
        ]);
        $token = $regularUser->createToken('api-token')->plainTextToken;

        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/questions', [
            'statement' => 'Questão sem permissão',
            'alternative_a' => 'A',
            'alternative_b' => 'B',
            'alternative_c' => 'C',
            'alternative_d' => 'D',
            'correct_alternative' => 'a',
            'tip' => 'Dica',
            'difficulty_id' => 1,
            'class_id' => 1,
            'activity_type_id' => (int) ActivityType::query()->value('id'),
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Acesso negado.');
    }

    public function test_questions_endpoint_validates_quantity_and_filters(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->getJson('/api/questions?class_id=999&difficulty_id=999&activity_type_id=999&quantity=0');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['class_id', 'difficulty_id', 'activity_type_id', 'quantity']);
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

    protected function createQuestion(
        int $classId = 1,
        int $difficultyId = 1,
        ?int $activityTypeId = null,
        ?string $statement = null
    ): Question {
        $resolvedActivityTypeId = $activityTypeId ?? (int) ActivityType::query()->value('id');

        return Question::query()->create([
            'statement' => $statement ?? 'Enunciado de teste para questão',
            'alternative_a' => 'Alternativa A',
            'alternative_b' => 'Alternativa B',
            'alternative_c' => 'Alternativa C',
            'alternative_d' => 'Alternativa D',
            'correct_alternative' => 'a',
            'tip' => 'Dica da questão de teste',
            'difficulty_id' => $difficultyId,
            'class_id' => $classId,
            'activity_type_id' => $resolvedActivityTypeId,
        ]);
    }
}
