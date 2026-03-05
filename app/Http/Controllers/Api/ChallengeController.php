<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreChallengeRequest;
use App\Http\Requests\Api\UpdateChallengeRequest;
use App\Models\Challenge;
use Illuminate\Http\JsonResponse;

class ChallengeController extends Controller
{
    public function store(StoreChallengeRequest $request): JsonResponse
    {
        $challenge = Challenge::query()->create($request->validated());

        return response()->json([
            'message' => 'Desafio criado com sucesso.',
            'challenge' => $challenge,
        ], 201);
    }

    public function index(): JsonResponse
    {
        $challenges = Challenge::query()
            ->orderBy('xp_reward', 'desc')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'unit',
                'target_value',
                'xp_reward',
                'is_active',
            ]);

        return response()->json([
            'count' => $challenges->count(),
            'challenges' => $challenges,
        ]);
    }

    public function show(Challenge $challenge): JsonResponse
    {
        return response()->json([
            'challenge' => $challenge,
        ]);
    }

    public function update(UpdateChallengeRequest $request, Challenge $challenge): JsonResponse
    {
        $challenge->update($request->validated());

        return response()->json([
            'message' => 'Desafio atualizado com sucesso.',
            'challenge' => $challenge,
        ]);
    }

    public function destroy(Challenge $challenge): JsonResponse
    {
        $challenge->delete();

        return response()->json([
            'message' => 'Desafio deletado com sucesso.',
        ]);
    }
}
