<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            // Invalidate the token
            $user->currentAccessToken()->delete();

            return response()->json([
                'status' => false,
                'message' => 'تم تعطيل حسابك من قبل الإدارة',
                'code' => 'ACCOUNT_REVOKED'
            ], 403);
        }

        return $next($request);
    }
}
