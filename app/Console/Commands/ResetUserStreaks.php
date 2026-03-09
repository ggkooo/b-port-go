<?php

namespace App\Console\Commands;

use App\Models\UserStreak;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ResetUserStreaks extends Command
{
    protected $signature = 'user-streaks:reset';

    protected $description = 'Reseta a ofensiva dos usuários que não aumentaram a streak no dia anterior';

    public function handle(): int
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $resetCount = UserStreak::query()
            ->where(function ($query) use ($yesterday) {
                $query->whereNull('last_lesson_date')
                    ->orWhere('last_lesson_date', '<', $yesterday);
            })
            ->where('current_streak', '>', 0)
            ->update(['current_streak' => 0]);

        $this->info("{$resetCount} ofensivas resetadas.");

        return self::SUCCESS;
    }
}
