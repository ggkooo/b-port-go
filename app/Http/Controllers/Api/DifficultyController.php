<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Difficulty;
use Illuminate\Http\JsonResponse;

class DifficultyController extends Controller
{
    public function index(): JsonResponse
    {
        $difficulties = Difficulty::query()
            ->orderBy('id')
            ->get(['id', 'name']);

        return response()->json([
            'difficulties' => $difficulties,
        ]);
    }
}
