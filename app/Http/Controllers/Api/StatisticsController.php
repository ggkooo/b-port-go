<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json([
            'statistics' => [
                'total_questions' => Question::query()->count(),
                'total_users' => User::query()->count(),
                'total_challenges' => Challenge::query()->count(),
            ],
        ]);
    }
}
