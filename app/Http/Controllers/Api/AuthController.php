<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $firstName = $request->string('first_name')->toString();
        $lastName = $request->string('last_name')->toString();

        $user = User::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $firstName.' '.$lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->string('email')->toString(),
            'phone' => null,
            'state' => null,
            'city' => null,
            'school' => null,
            'class' => null,
            'shift' => null,
            'password' => $request->string('password')->toString(),
        ]);

        return response()->json([
            'message' => 'Usuário cadastrado com sucesso.',
            'user' => $user,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'uuid' => $user->uuid,
            'email' => $user->email,
            'token' => $token,
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $user = User::query()->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'user' => $user,
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Link de redefinição enviado para o seu e-mail.',
            ]);
        }

        return response()->json([
            'message' => 'Não foi possível enviar o link de redefinição.',
        ], 500);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $user) {
            return response()->json([
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        // Verificar se o token é válido
        $token = DB::table('password_reset_tokens')->where('email', $request->string('email')->toString())->first();

        if (! $token || ! hash_equals($request->string('token')->toString(), $token->token)) {
            return response()->json([
                'message' => 'Token inválido ou expirado.',
            ], 400);
        }

        // Atualizar a senha
        $user->password = $request->string('password')->toString();
        $user->save();

        // Deletar o token após uso
        DB::table('password_reset_tokens')->where('email', $request->string('email')->toString())->delete();

        return response()->json([
            'message' => 'Senha redefinida com sucesso.',
        ]);
    }
}
