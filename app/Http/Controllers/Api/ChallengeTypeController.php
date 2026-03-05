<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChallengeType;
use Illuminate\Http\JsonResponse;

class ChallengeTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $challengeTypes = ChallengeType::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'description',
            ]);

        return response()->json([
            'count' => $challengeTypes->count(),
            'challenge_types' => $challengeTypes,
        ]);
    }
}
