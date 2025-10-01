<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginUserRequest;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function login(LoginUserRequest $request)
    {
        try {
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
                $token = $user->createToken('user_token', expiresAt: now()->addDay())->plainTextToken;
                $role = $user->getRoleNames()->first();
                $user['role'] = $role;
                unset($user['roles']);
                return response()->json([
                    'status_code' => 200,
                    'message' => 'User Login Successfully',
                    'user' => $user,
                    'token' => $token,
                ], 200);
            } else {
                return ResponseTrait::error('The provided credentials do not match our records.');
            }
        } catch (Exception $e) {
            return ResponseTrait::error('The provided credentials do not match our records.');
        }
    }

    public function logout()
    {
        try {
            $user = Auth::user();
            $user->tokens()->delete();
            return ResponseTrait::success('User Logged Out Successfully');
        } catch (Exception $e) {
            return ResponseTrait::error('An error occurred due to: ' . $e->getMessage());
        }
    }
}
