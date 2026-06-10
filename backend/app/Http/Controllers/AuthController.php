<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    // (Register)
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:client,owner' 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
        ]);

        $token = Auth::guard('api')->login($user);

        return $this->respondWithToken($token, $user);
    }

    // دالة تسجيل الدخول (Login)
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized - البيانات غالطة'], 401);
        }

        return $this->respondWithToken($token, auth('api')->user());
    }

    // دالة تسجيل الخروج (Logout)
    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    // دالة جلب معلومات المستخدم الحالي
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    // دالة مساعدة باش نجمعو الـ Token مع معلومات اليوزر
    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $user
        ]);
    }

    // Token refresh
    public function refresh()
    {
        $token = Auth::guard('api')->refresh();
        return $this->respondWithToken($token, auth('api')->user());
    }
}
