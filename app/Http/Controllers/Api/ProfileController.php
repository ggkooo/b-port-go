<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(string $uuid): JsonResponse
    {
        $user = User::findByUuidOrFail($uuid);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'phone' => $request->string('phone')->toString(),
            'email' => $request->string('email')->toString(),
            'state' => $request->string('state')->toString(),
            'city' => $request->string('city')->toString(),
            'school' => $request->string('school')->toString(),
            'class' => $request->integer('class'),
            'shift' => $request->integer('shift'),
        ])->save();

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'user' => $user->fresh(),
        ]);
    }

    public function profile(Request $request, string $uuid): JsonResponse
    {
        $user = User::query()
            ->whereKey($request->user()->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        return response()->json([
            'user' => $user,
        ]);
    }
}
