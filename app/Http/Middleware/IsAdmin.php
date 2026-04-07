<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user =$request->user();
        if(!$user || $user->user_type != User::TYPE_ADMIN){
            return response()->json([
                'status'=> false,
                'message' => 'غير مصرح لك بالدخول (Admins Only)'
            ],403);
        }
        return $next($request);
    }
}
