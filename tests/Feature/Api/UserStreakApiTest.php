<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserStreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserStreakApiTest extends TestCase
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
    protected function apiHeaders(): array
    {
        return [
            'X-API-KEY' => 'portgo-test-key',
        ];
    }

    public function test_user_can_fetch_streak_summary(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/users/'.$user->uuid.'/streak');

        $response
            ->assertOk()
            ->assertJsonPath('user_uuid', $user->uuid)
            ->assertJsonPath('current_streak', 0)
            ->assertJsonPath('best_streak', 0)
            ->assertJsonPath('lesson_done_today', false)
            ->assertJsonPath('last_lesson_date', null);

        $this->assertDatabaseHas('user_streaks', [
            'user_id' => $user->id,
            'current_streak' => 0,
            'best_streak' => 0,
            'last_lesson_date' => null,
        ]);
    }

    public function test_complete_today_is_idempotent_on_same_day(): void
    {
        Carbon::setTestNow('2026-03-04 10:00:00');

        $user = User::factory()->create();

        $firstResponse = $this->withHeaders($this->apiHeaders())
            ->patchJson('/api/users/'.$user->uuid.'/streak/complete-today');

        $firstResponse
            ->assertOk()
            ->assertJsonPath('current_streak', 1)
            ->assertJsonPath('best_streak', 1)
            ->assertJsonPath('lesson_done_today', true)
            ->assertJsonPath('last_lesson_date', '2026-03-04');

        $secondResponse = $this->withHeaders($this->apiHeaders())
            ->patchJson('/api/users/'.$user->uuid.'/streak/complete-today');

        $secondResponse
            ->assertOk()
            ->assertJsonPath('message', 'Lição de hoje já registrada.')
            ->assertJsonPath('current_streak', 1)
            ->assertJsonPath('best_streak', 1)
            ->assertJsonPath('lesson_done_today', true)
            ->assertJsonPath('last_lesson_date', '2026-03-04');

        $this->assertDatabaseHas('user_streaks', [
            'user_id' => $user->id,
            'current_streak' => 1,
            'best_streak' => 1,
        ]);

        $this->assertSame('2026-03-04', UserStreak::query()->where('user_id', $user->id)->firstOrFail()->last_lesson_date?->toDateString());

        Carbon::setTestNow();
    }

    public function test_complete_today_increments_existing_consecutive_streak(): void
    {
        Carbon::setTestNow('2026-03-05 10:00:00');

        $user = User::factory()->create();

        UserStreak::query()->create([
            'user_id' => $user->id,
            'last_lesson_date' => '2026-03-04',
            'current_streak' => 3,
            'best_streak' => 3,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->patchJson('/api/users/'.$user->uuid.'/streak/complete-today');

        $response
            ->assertOk()
            ->assertJsonPath('current_streak', 4)
            ->assertJsonPath('best_streak', 4)
            ->assertJsonPath('lesson_done_today', true)
            ->assertJsonPath('last_lesson_date', '2026-03-05');

        $this->assertDatabaseHas('user_streaks', [
            'user_id' => $user->id,
            'current_streak' => 4,
            'best_streak' => 4,
        ]);

        $this->assertSame('2026-03-05', UserStreak::query()->where('user_id', $user->id)->firstOrFail()->last_lesson_date?->toDateString());

        Carbon::setTestNow();
    }

    public function test_can_check_if_today_lesson_was_done(): void
    {
        Carbon::setTestNow('2026-03-04 15:00:00');

        $user = User::factory()->create();

        UserStreak::query()->create([
            'user_id' => $user->id,
            'last_lesson_date' => '2026-03-04',
            'current_streak' => 2,
            'best_streak' => 5,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/users/'.$user->uuid.'/streak/check-today');

        $response
            ->assertOk()
            ->assertJsonPath('user_uuid', $user->uuid)
            ->assertJsonPath('date', '2026-03-04')
            ->assertJsonPath('lesson_done_today', true)
            ->assertJsonPath('last_lesson_date', '2026-03-04');

        Carbon::setTestNow();
    }
}
