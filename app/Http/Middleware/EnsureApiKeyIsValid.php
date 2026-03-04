<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKeyIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestApiKey = $request->header('X-API-KEY');
        $configuredApiKey = (string) config('services.portgo.api_key');

        if ($requestApiKey === null || $configuredApiKey === '' || ! hash_equals($configuredApiKey, $requestApiKey)) {
            return response()->json([
                'message' => 'API key inválida.',
            ], 401);
        }

        return $next($request);
    }
}
