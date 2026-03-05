<?php

use App\Http\Controllers\Api\ActivityTypeController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\ChallengeTypeController;
use App\Http\Controllers\Api\DailyChallengeController;
use App\Http\Controllers\Api\DifficultyController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\UserStreakController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function (): void {
    Route::controller(AuthController::class)->group(function (): void {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
        Route::post('/forgot-password', 'forgotPassword');
        Route::post('/reset-password', 'resetPassword');
    });

    Route::controller(ProfileController::class)->group(function (): void {
        Route::get('/users/{uuid}', 'show');
    });

    Route::get('/classes', [SchoolClassController::class, 'index']);
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::get('/difficulties', [DifficultyController::class, 'index']);
    Route::get('/activity-types', [ActivityTypeController::class, 'index']);
    Route::get('/challenge-types', [ChallengeTypeController::class, 'index']);
    Route::get('/statistics/overview', [StatisticsController::class, 'overview']);

    Route::controller(QuestionController::class)->group(function (): void {
        Route::get('/questions', 'index');
        Route::get('/questions/{question}', 'show');
    });

    Route::controller(ChallengeController::class)->group(function (): void {
        Route::get('/challenges', 'index');
        Route::get('/challenges/{challenge}', 'show');
    });

    Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
        Route::post('/questions', [QuestionController::class, 'store']);
        Route::patch('/questions/{question}', [QuestionController::class, 'update']);
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);

        Route::post('/challenges', [ChallengeController::class, 'store']);
        Route::patch('/challenges/{challenge}', [ChallengeController::class, 'update']);
        Route::delete('/challenges/{challenge}', [ChallengeController::class, 'destroy']);
    });

    Route::controller(DailyChallengeController::class)->group(function (): void {
        Route::get('/users/{uuid}/challenges/today', 'today');
        Route::patch('/users/{uuid}/challenges/{dailyChallenge}/progress', 'updateProgress');
    });

    Route::controller(UserStreakController::class)->group(function (): void {
        Route::get('/users/{uuid}/streak', 'show');
        Route::patch('/users/{uuid}/streak/complete-today', 'completeToday');
        Route::get('/users/{uuid}/streak/check-today', 'checkToday');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/profile/{uuid}', [ProfileController::class, 'profile']);
        Route::patch('/profile', [ProfileController::class, 'updateProfile']);
    });

    Route::middleware(['admin'])->group(function (): void {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::patch('/users/{user:uuid}', [AdminUserController::class, 'update']);
        Route::delete('/users/{user:uuid}', [AdminUserController::class, 'destroy']);
        Route::patch('/users/{user:uuid}/promote', [AdminUserController::class, 'promoteToAdmin']);
        Route::patch('/users/{user:uuid}/remove-admin', [AdminUserController::class, 'removeAdmin']);
    });
});
