<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.portgo.api_key', 'portgo-test-key');
    }

    /**
     * @return array<string, string>
     */
    protected function adminHeaders(): array
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        return [
            'X-API-KEY' => 'portgo-test-key',
            'X-ADMIN-UUID' => $admin->uuid,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function userHeaders(): array
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        return [
            'X-API-KEY' => 'portgo-test-key',
            'X-ADMIN-UUID' => $user->uuid,
        ];
    }

    public function test_admin_can_list_all_users(): void
    {
        User::factory()->count(2)->create();

        $response = $this->withHeaders($this->adminHeaders())
            ->getJson('/api/users');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'users' => [
                    ['id', 'uuid', 'name', 'first_name', 'last_name', 'email', 'is_admin'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('users')));
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $response = $this->withHeaders($this->userHeaders())
            ->getJson('/api/users');

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Acesso negado.');
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/users', [
                'first_name' => 'Maria',
                'last_name' => 'Silva',
                'email' => 'maria.admin@example.com',
                'password' => 'password123',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Usuário criado com sucesso.')
            ->assertJsonPath('user.email', 'maria.admin@example.com')
            ->assertJsonPath('user.is_admin', false);

        $this->assertDatabaseHas('users', [
            'email' => 'maria.admin@example.com',
            'first_name' => 'Maria',
            'last_name' => 'Silva',
            'is_admin' => false,
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $targetUser = User::factory()->create([
            'first_name' => 'João',
            'last_name' => 'Souza',
            'name' => 'João Souza',
            'email' => 'joao.souza@example.com',
            'is_admin' => false,
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->patchJson('/api/users/'.$targetUser->uuid, [
                'first_name' => 'João Pedro',
                'last_name' => 'Souza Lima',
                'email' => 'joao.pedro@example.com',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Usuário atualizado com sucesso.')
            ->assertJsonPath('user.email', 'joao.pedro@example.com')
            ->assertJsonPath('user.is_admin', false)
            ->assertJsonPath('user.name', 'João Pedro Souza Lima');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'first_name' => 'João Pedro',
            'last_name' => 'Souza Lima',
            'name' => 'João Pedro Souza Lima',
            'email' => 'joao.pedro@example.com',
            'is_admin' => false,
        ]);
    }

    public function test_admin_can_promote_user_to_admin(): void
    {
        $targetUser = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->patchJson('/api/users/'.$targetUser->uuid.'/promote');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Usuário promovido para administrador com sucesso.')
            ->assertJsonPath('user.is_admin', true);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->withHeaders($this->adminHeaders())
            ->deleteJson('/api/users/'.$targetUser->uuid);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Usuário excluído com sucesso.');

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_admin_can_remove_admin_privilege_from_user(): void
    {
        $targetUser = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->patchJson('/api/users/'.$targetUser->uuid.'/remove-admin');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Privilégio de administrador removido com sucesso.')
            ->assertJsonPath('user.is_admin', false);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'is_admin' => false,
        ]);
    }

    public function test_create_user_validation_returns_json_even_without_json_accept_header(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->post('/api/users', []);

        $response
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('message', 'Os dados informados são inválidos.')
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
    }
}
