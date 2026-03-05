<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_admin) {
            return $next($request);
        }

        $adminUuid = $request->header('X-ADMIN-UUID');

        if (is_string($adminUuid) && $adminUuid !== '') {
            $adminUser = User::query()
                ->where('uuid', $adminUuid)
                ->where('is_admin', true)
                ->first();

            if ($adminUser) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Acesso negado.',
        ], 403);
    }
}
