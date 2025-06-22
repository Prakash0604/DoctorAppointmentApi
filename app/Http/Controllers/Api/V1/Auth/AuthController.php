<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(AuthRequest $request)
    {
        $user = User::create($request->validated());
        if ($user) {
            return $this->sendResponse(['user' => $user], 'User registered successfully', 201);
        } else {
            return $this->sendError('Internal Server Error', 500);
        }
    }

    public function login(AuthRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $data['token'] = $user->createToken('auth-token')->accessToken;
            $data['name'] = $user->name;
            $data['role'] = $user->roleName();
            return $this->sendResponse($data, 'Login Successfully', 200);
        }
        return $this->sendError('Invalid Login Crediantials', 401);
    }

    public function profile()
    {
        $user = Auth::user();
        if ($user) {
            return $this->sendResponse($user, 'Profile Information', 200);
        }
        return $this->sendError('Unauthenticated', 401);
    }

     public function logout(Request $request)
    {
        $token = $request->user()->token();
        if ($token) {
            $token->revoke();
            return $this->sendResponse('', 'Logout Successfully', 200);
        } else {
            return $this->sendError('Something went wrong', 404);
        }
    }
}
