<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SwitchRole
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $selectedRole = $request->header('X-Selected-Role') ?? $request->query('selected_role');

        if (!$selectedRole || !in_array($selectedRole,$user->roles)) {
            return response()->json(['message' => 'Invalid selected role'], 403);
        }

        session(['selected_role' => $selectedRole]);

        return $next($request);
    }
}
