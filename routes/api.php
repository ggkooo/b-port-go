<?php

use App\Http\Controllers\Api\ActivityTypeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DailyChallengeController;
use App\Http\Controllers\Api\DifficultyController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\UserStreakController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/users/{uuid}', [ProfileController::class, 'show']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/classes', [SchoolClassController::class, 'index']);
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::get('/difficulties', [DifficultyController::class, 'index']);
    Route::get('/activity-types', [ActivityTypeController::class, 'index']);
    Route::get('/questions', [QuestionController::class, 'index']);
    Route::get('/users/{uuid}/challenges/today', [DailyChallengeController::class, 'today']);
    Route::patch('/users/{uuid}/challenges/{dailyChallenge}/progress', [DailyChallengeController::class, 'updateProgress']);
    Route::get('/users/{uuid}/streak', [UserStreakController::class, 'show']);
    Route::patch('/users/{uuid}/streak/complete-today', [UserStreakController::class, 'completeToday']);
    Route::get('/users/{uuid}/streak/check-today', [UserStreakController::class, 'checkToday']);
    Route::get('/profile/{uuid}', [ProfileController::class, 'profile'])->middleware('auth:sanctum');
    Route::patch('/profile', [ProfileController::class, 'updateProfile'])->middleware('auth:sanctum');
});
