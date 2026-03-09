<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserStreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetUserStreaksCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_resets_streaks_for_inactive_users(): void
    {
        $user = User::factory()->create();
        $streak = UserStreak::create([
            'user_id' => $user->id,
            'current_streak' => 5,
            'best_streak' => 5,
            'last_lesson_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan('user-streaks:reset')->assertExitCode(0);
        $streak->refresh();
        $this->assertEquals(0, $streak->current_streak);
        $this->assertEquals(5, $streak->best_streak);
    }

    public function test_does_not_reset_active_streaks(): void
    {
        $user = User::factory()->create();
        $streak = UserStreak::create([
            'user_id' => $user->id,
            'current_streak' => 3,
            'best_streak' => 3,
            'last_lesson_date' => now()->toDateString(),
        ]);

        $this->artisan('user-streaks:reset')->assertExitCode(0);
        $streak->refresh();
        $this->assertEquals(3, $streak->current_streak);
    }
}
