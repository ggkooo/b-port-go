<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateDailyChallengeProgressRequest;
use App\Models\Challenge;
use App\Models\DailyChallenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class DailyChallengeController extends Controller
{
    public function today(string $uuid): JsonResponse
    {
        $user = User::query()->where('uuid', $uuid)->firstOrFail();

        $this->pruneOlderThanTwoDays($user);
        $challenges = $this->ensureTodayChallenges($user);

        return response()->json([
            'user_uuid' => $user->uuid,
            'date' => now()->toDateString(),
            'count' => $challenges->count(),
            'challenges' => $challenges,
        ]);
    }

    public function updateProgress(UpdateDailyChallengeProgressRequest $request, string $uuid, DailyChallenge $dailyChallenge): JsonResponse
    {
        $user = User::query()->where('uuid', $uuid)->firstOrFail();
        $today = now()->toDateString();

        abort_unless(
            $dailyChallenge->user_id === $user->id
            && $dailyChallenge->challenge_date->toDateString() === $today,
            404
        );

        if ($dailyChallenge->completed_at === null) {
            $newValue = min(
                $dailyChallenge->current_value + (int) $request->integer('increment'),
                $dailyChallenge->target_value
            );

            $dailyChallenge->forceFill([
                'current_value' => $newValue,
                'completed_at' => $newValue >= $dailyChallenge->target_value ? now() : null,
            ])->save();
        }

        $challenges = $this->loadTodayChallenges($user);

        return response()->json([
            'user_uuid' => $user->uuid,
            'date' => $today,
            'count' => $challenges->count(),
            'challenges' => $challenges,
        ]);
    }

    /**
     * @return Collection<int, DailyChallenge>
     */
    protected function ensureTodayChallenges(User $user): Collection
    {
        $today = now()->toDateString();

        $existingChallenges = DailyChallenge::query()
            ->where('user_id', $user->id)
            ->whereDate('challenge_date', $today)
            ->orderBy('position')
            ->get();

        if ($existingChallenges->count() >= 3) {
            return $this->loadTodayChallenges($user);
        }

        $assignedChallengeIds = $existingChallenges
            ->pluck('challenge_id')
            ->filter()
            ->values()
            ->all();

        $selectedChallenges = collect();

        if ($existingChallenges->where('xp_reward', 30)->count() < 2) {
            $neededThirtyXp = 2 - $existingChallenges->where('xp_reward', 30)->count();

            $thirtyXpChallenges = Challenge::query()
                ->where('is_active', true)
                ->where('xp_reward', 30)
                ->when(
                    ! empty($assignedChallengeIds),
                    fn ($query) => $query->whereNotIn('id', $assignedChallengeIds)
                )
                ->inRandomOrder()
                ->limit($neededThirtyXp)
                ->get();

            $selectedChallenges = $selectedChallenges->merge($thirtyXpChallenges);
            $assignedChallengeIds = array_merge($assignedChallengeIds, $thirtyXpChallenges->pluck('id')->all());
        }

        if ($existingChallenges->where('xp_reward', 50)->count() < 1) {
            $fiftyXpChallenge = Challenge::query()
                ->where('is_active', true)
                ->where('xp_reward', 50)
                ->when(
                    ! empty($assignedChallengeIds),
                    fn ($query) => $query->whereNotIn('id', $assignedChallengeIds)
                )
                ->inRandomOrder()
                ->first();

            if ($fiftyXpChallenge !== null) {
                $selectedChallenges->push($fiftyXpChallenge);
                $assignedChallengeIds[] = $fiftyXpChallenge->id;
            }
        }

        $remainingSlots = 3 - ($existingChallenges->count() + $selectedChallenges->count());

        if ($remainingSlots > 0) {
            $fallbackChallenges = Challenge::query()
                ->where('is_active', true)
                ->when(
                    ! empty($assignedChallengeIds),
                    fn ($query) => $query->whereNotIn('id', $assignedChallengeIds)
                )
                ->inRandomOrder()
                ->limit($remainingSlots)
                ->get();

            $selectedChallenges = $selectedChallenges->merge($fallbackChallenges);
        }

        $nextPosition = $existingChallenges->count() + 1;

        foreach ($selectedChallenges as $challenge) {
            DailyChallenge::query()->create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'challenge_name' => $challenge->name,
                'unit' => $challenge->unit,
                'target_value' => $challenge->target_value,
                'current_value' => 0,
                'xp_reward' => $challenge->xp_reward,
                'challenge_date' => $today,
                'position' => $nextPosition,
            ]);

            $nextPosition++;
        }

        return $this->loadTodayChallenges($user);
    }

    /**
     * @return Collection<int, DailyChallenge>
     */
    protected function loadTodayChallenges(User $user): Collection
    {
        return DailyChallenge::query()
            ->where('user_id', $user->id)
            ->whereDate('challenge_date', now()->toDateString())
            ->orderBy('position')
            ->get([
                'id',
                'user_id',
                'challenge_id',
                'challenge_name',
                'unit',
                'target_value',
                'current_value',
                'xp_reward',
                'challenge_date',
                'position',
                'completed_at',
            ]);
    }

    protected function pruneOlderThanTwoDays(User $user): void
    {
        DailyChallenge::query()
            ->where('user_id', $user->id)
            ->whereDate('challenge_date', '<', now()->subDay()->toDateString())
            ->delete();
    }
}
