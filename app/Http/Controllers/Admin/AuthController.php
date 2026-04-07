<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (\Illuminate\Support\Facades\Auth::attempt(['email' => $request->email, 'password' => $request->password, 'user_type' => User::TYPE_ADMIN])) {
            $request->session()->regenerate();

            if ($request->wantsJson()) {
                $user = \Illuminate\Support\Facades\Auth::user();
                $token = $user->createToken('admin_token')->plainTextToken;
                return response()->json([
                    'status' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                    'admin' => $user
                ]);
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات الاعتماد المقدمة لا تتطابق مع سجلاتنا.',
            ], 401);
        }

        return back()->withErrors([
            'email' => 'بيانات الاعتماد المقدمة لا تتطابق مع سجلاتنا.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if ($request->wantsJson()) {
            $request->user()->tokens()->delete();
            return response()->json([
                'status' => true,
                'message' => 'Logged out successfully',
            ]);
        }

        \Illuminate\Support\Facades\Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
