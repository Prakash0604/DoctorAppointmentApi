<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\ProfileUpdateRequest;
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
            $user =  Auth::user();
            $data['token'] = $user->createToken('auth-token')->accessToken;
            $data['name'] = $user->name;
            $data['role'] = $user->roleName();
            return $this->sendResponse($data, 'Login Successfully', 200);
        }
        return $this->sendError('Invalid Login Crediantials', 401);
    }

    public function profile()
    {
        $user =  Auth::user();
        if ($user) {
            return $this->sendResponse($user, 'Profile Information', 200);
        }
        return $this->sendError('Unauthenticated', 401);
    }


    public function profileUpdate(ProfileUpdateRequest $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->sendError('User not authenticated', 401);
        }

        $data = $request->validated();

        if ($request->hasFile('profile_image')) {
            switch ($user->role->name ?? null) {
                case 'Admin':
                    $folder = 'admin/profile';
                    break;
                case 'Doctor':
                    $folder = 'doctor/profile';
                    break;
                case 'Patient':
                    $folder = 'patient/profile';
                    break;
                default:
                    $folder = 'patient/profile';
                    break;
            }

            if ($user->profile_image) {
                $filePath = $user->profile_image;

                if (!str_contains($filePath, $folder)) {
                    $filePath = $folder . '/' . $filePath;
                }

                $this->removePublicFile($filePath);
            }

            $data['profile_image'] = $this->uploadfiles($request->file('profile_image'), $folder);
        }

        if ($user->update($data)) {
            return $this->sendResponse($user, 'Profile Updated Successfully', 200);
        }

        return $this->sendError('Something went wrong', 500);
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
