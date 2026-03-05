<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAdminUserRequest;
use App\Http\Requests\Api\UpdateAdminUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->with([
                'schoolClass:id,name',
                'schoolShift:id,name',
            ])
            ->orderBy('id')
            ->get([
                'id',
                'uuid',
                'name',
                'first_name',
                'last_name',
                'email',
                'phone',
                'state',
                'city',
                'school',
                'class',
                'shift',
                'is_admin',
                'created_at',
                'updated_at',
            ]);

        return response()->json([
            'users' => $users,
        ]);
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $firstName = (string) $validated['first_name'];
        $lastName = (string) $validated['last_name'];

        $user = User::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => trim($firstName.' '.$lastName),
            ...$validated,
            'is_admin' => false,
        ]);

        $user->load([
            'schoolClass:id,name',
            'schoolShift:id,name',
        ]);

        return response()->json([
            'message' => 'Usuário criado com sucesso.',
            'user' => $user,
        ], 201);
    }

    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (array_key_exists('first_name', $validated) || array_key_exists('last_name', $validated)) {
            $firstName = (string) ($validated['first_name'] ?? $user->first_name);
            $lastName = (string) ($validated['last_name'] ?? $user->last_name);
            $validated['name'] = trim($firstName.' '.$lastName);
        }

        $user->update($validated);

        $user->load([
            'schoolClass:id,name',
            'schoolShift:id,name',
        ]);

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'user' => $user,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuário excluído com sucesso.',
        ]);
    }

    public function promoteToAdmin(User $user): JsonResponse
    {
        $user->update([
            'is_admin' => true,
        ]);

        return response()->json([
            'message' => 'Usuário promovido para administrador com sucesso.',
            'user' => $user,
        ]);
    }

    public function removeAdmin(User $user): JsonResponse
    {
        $user->update([
            'is_admin' => false,
        ]);

        return response()->json([
            'message' => 'Privilégio de administrador removido com sucesso.',
            'user' => $user,
        ]);
    }
}
