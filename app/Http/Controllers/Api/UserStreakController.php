<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserStreak;
use Illuminate\Http\JsonResponse;

class UserStreakController extends Controller
{
    public function show(string $uuid): JsonResponse
    {
        $user = User::query()->where('uuid', $uuid)->firstOrFail();
        $streak = $this->resolveStreak($user);
        $today = now()->toDateString();

        return response()->json([
            'user_uuid' => $user->uuid,
            'current_streak' => $streak->current_streak,
            'best_streak' => $streak->best_streak,
            'last_lesson_date' => $streak->last_lesson_date?->toDateString(),
            'lesson_done_today' => $streak->last_lesson_date?->toDateString() === $today,
        ]);
    }

    public function checkToday(string $uuid): JsonResponse
    {
        $user = User::query()->where('uuid', $uuid)->firstOrFail();
        $streak = $this->resolveStreak($user);
        $today = now()->toDateString();

        return response()->json([
            'user_uuid' => $user->uuid,
            'date' => $today,
            'lesson_done_today' => $streak->last_lesson_date?->toDateString() === $today,
            'last_lesson_date' => $streak->last_lesson_date?->toDateString(),
        ]);
    }

    public function completeToday(string $uuid): JsonResponse
    {
        $user = User::query()->where('uuid', $uuid)->firstOrFail();
        $streak = $this->resolveStreak($user);

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $lastLessonDate = $streak->last_lesson_date?->toDateString();

        if ($lastLessonDate === $today) {
            return response()->json([
                'message' => 'Lição de hoje já registrada.',
                'user_uuid' => $user->uuid,
                'current_streak' => $streak->current_streak,
                'best_streak' => $streak->best_streak,
                'last_lesson_date' => $lastLessonDate,
                'lesson_done_today' => true,
            ]);
        }

        $newCurrentStreak = $lastLessonDate === $yesterday
            ? $streak->current_streak + 1
            : 1;

        $streak->forceFill([
            'last_lesson_date' => $today,
            'current_streak' => $newCurrentStreak,
            'best_streak' => max($streak->best_streak, $newCurrentStreak),
        ])->save();

        return response()->json([
            'message' => 'Lição do dia registrada com sucesso.',
            'user_uuid' => $user->uuid,
            'current_streak' => $streak->current_streak,
            'best_streak' => $streak->best_streak,
            'last_lesson_date' => $streak->last_lesson_date?->toDateString(),
            'lesson_done_today' => true,
        ]);
    }

    protected function resolveStreak(User $user): UserStreak
    {
        return UserStreak::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'last_lesson_date' => null,
                'current_streak' => 0,
                'best_streak' => 0,
            ]
        );
    }
}
