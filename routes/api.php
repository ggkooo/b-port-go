<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DifficultyController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\ShiftController;
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
    Route::get('/profile/{uuid}', [ProfileController::class, 'profile'])->middleware('auth:sanctum');
    Route::patch('/profile', [ProfileController::class, 'updateProfile'])->middleware('auth:sanctum');
});
