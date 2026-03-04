<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use Illuminate\Http\JsonResponse;

class ActivityTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $activityTypes = ActivityType::query()
            ->orderBy('id')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'activity_types' => $activityTypes,
        ]);
    }
}
