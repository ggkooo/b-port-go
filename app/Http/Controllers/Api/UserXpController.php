<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUserXpRequest;
use App\Models\User;
use App\Models\UserXp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserXpController extends Controller
{
    public function update(UpdateUserXpRequest $request, string $uuid): JsonResponse
    {
        $authenticatedUser = $request->user();
        $targetUser = User::findByUuidOrFail($uuid);

        if ($authenticatedUser === null) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        if ($authenticatedUser->id !== $targetUser->id && ! $authenticatedUser->is_admin) {
            return response()->json([
                'message' => 'Acesso negado.',
            ], 403);
        }

        $userXp = UserXp::query()->firstOrCreate(
            ['user_id' => $targetUser->id],
            ['xp_amount' => 0]
        );

        $userXp->forceFill([
            'xp_amount' => $userXp->xp_amount + (int) $request->integer('xp'),
        ])->save();

        return response()->json([
            'message' => 'XP atualizado com sucesso.',
            'xp' => [
                'id' => $userXp->id,
                'user_id' => $userXp->user_id,
                'xp_amount' => $userXp->xp_amount,
            ],
        ]);
    }

    public function ranking(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        $rankedUsers = User::query()
            ->leftJoin('user_xps', 'user_xps.user_id', '=', 'users.id')
            ->leftJoin('user_streaks', 'user_streaks.user_id', '=', 'users.id')
            ->select('users.id', 'users.uuid', 'users.name')
            ->selectRaw('COALESCE(user_streaks.current_streak, 0) as offensive')
            ->selectRaw('COALESCE(user_xps.xp_amount, 0) as xp_amount')
            ->orderByRaw('COALESCE(user_xps.xp_amount, 0) desc')
            ->orderByRaw('COALESCE(user_streaks.current_streak, 0) desc')
            ->orderBy('users.id')
            ->get();

        $rankingEntries = $rankedUsers
            ->values()
            ->map(function (User $rankedUser, int $index): array {
                return [
                    'position' => $index + 1,
                    'user_uuid' => $rankedUser->uuid,
                    'name' => $rankedUser->name,
                    'offensive' => (int) $rankedUser->offensive,
                    'xp_amount' => (int) $rankedUser->xp_amount,
                ];
            });

        $topTen = $rankingEntries->take(10)->values();

        $loggedUserRanking = $rankingEntries
            ->firstWhere('user_uuid', $authenticatedUser->uuid);

        return response()->json([
            'ranking' => $topTen,
            'top_10' => $topTen,
            'logged_user' => $loggedUserRanking,
        ]);
    }
}
