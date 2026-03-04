<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;

class ShiftController extends Controller
{
    public function index(): JsonResponse
    {
        $shifts = Shift::query()
            ->orderBy('id')
            ->get(['id', 'name']);

        return response()->json([
            'shifts' => $shifts,
        ]);
    }
}
