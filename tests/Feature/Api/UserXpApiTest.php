<?php

namespace Tests\Feature\Api;

use App\Models\DailyChallenge;
use App\Models\User;
use App\Models\UserStreak;
use App\Models\UserXp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserXpApiTest extends TestCase
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
    protected function authHeaders(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'X-API-KEY' => 'portgo-test-key',
            'Authorization' => 'Bearer '.$token,
        ];
    }

    public function test_authenticated_user_can_increment_own_xp(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->patchJson('/api/users/'.$user->uuid.'/xp', [
                'xp' => 30,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'XP atualizado com sucesso.')
            ->assertJsonPath('xp.user_id', $user->id)
            ->assertJsonPath('xp.xp_amount', 30);

        $this->assertDatabaseHas('user_xps', [
            'user_id' => $user->id,
            'xp_amount' => 30,
        ]);
    }

    public function test_non_admin_user_cannot_increment_another_user_xp(): void
    {
        $authenticatedUser = User::factory()->create([
            'is_admin' => false,
        ]);
        $targetUser = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($authenticatedUser))
            ->patchJson('/api/users/'.$targetUser->uuid.'/xp', [
                'xp' => 25,
            ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Acesso negado.');

        $this->assertDatabaseMissing('user_xps', [
            'user_id' => $targetUser->id,
        ]);
    }

    public function test_ranking_returns_top_ten_and_logged_user_outside_top_ten(): void
    {
        $loggedUser = User::factory()->create();

        UserXp::query()->create([
            'user_id' => $loggedUser->id,
            'xp_amount' => 1,
        ]);

        UserStreak::query()->create([
            'user_id' => $loggedUser->id,
            'last_lesson_date' => now()->toDateString(),
            'current_streak' => 4,
            'best_streak' => 4,
        ]);

        $otherUsers = User::factory()->count(11)->create();

        foreach ($otherUsers as $index => $otherUser) {
            UserXp::query()->create([
                'user_id' => $otherUser->id,
                'xp_amount' => 100 - $index,
            ]);
        }

        $response = $this->withHeaders($this->authHeaders($loggedUser))
            ->getJson('/api/ranking');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'ranking')
            ->assertJsonCount(10, 'top_10')
            ->assertJsonPath('logged_user.user_uuid', $loggedUser->uuid)
            ->assertJsonPath('logged_user.position', 12)
            ->assertJsonPath('logged_user.offensive', 4)
            ->assertJsonPath('logged_user.xp_amount', 1);
    }

    public function test_completing_daily_challenge_awards_xp_only_once(): void
    {
        $user = User::factory()->create();

        $dailyChallenge = DailyChallenge::query()->create([
            'user_id' => $user->id,
            'challenge_id' => null,
            'challenge_name' => 'Ler conteúdo',
            'unit' => 'vezes',
            'target_value' => 10,
            'current_value' => 9,
            'xp_reward' => 30,
            'challenge_date' => now()->toDateString(),
            'position' => 1,
            'completed_at' => null,
        ]);

        $firstResponse = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->patchJson('/api/users/'.$user->uuid.'/challenges/'.$dailyChallenge->id.'/progress', [
            'increment' => 1,
        ]);

        $firstResponse
            ->assertOk()
            ->assertJsonPath('awarded_xp', 30);

        $this->assertDatabaseHas('user_xps', [
            'user_id' => $user->id,
            'xp_amount' => 30,
        ]);

        $secondResponse = $this->withHeaders([
            'X-API-KEY' => 'portgo-test-key',
        ])->patchJson('/api/users/'.$user->uuid.'/challenges/'.$dailyChallenge->id.'/progress', [
            'increment' => 1,
        ]);

        $secondResponse
            ->assertOk()
            ->assertJsonPath('awarded_xp', 0);

        $this->assertDatabaseHas('user_xps', [
            'user_id' => $user->id,
            'xp_amount' => 30,
        ]);
    }
}
