<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function register(AuthRequest $request)
    {
        $user = User::create($request->validated());
        if ($user) {
            return $this->sendResponse(['user' => $user], 'User registered successfully', Response::HTTP_CREATED);
        } else {
            return $this->sendError('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
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
            return $this->sendResponse($data, 'Login Successfully', Response::HTTP_OK);
        }
        return $this->sendError('Invalid Login Crediantials', Response::HTTP_UNAUTHORIZED);
    }

    public function profile()
    {
        $user =  Auth::user();
        if ($user) {
            return $this->sendResponse($user, 'Profile Information', Response::HTTP_OK);
        }
        return $this->sendError('Unauthenticated', Response::HTTP_UNAUTHORIZED);
    }


    public function profileUpdate(ProfileUpdateRequest $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->sendError('User not authenticated', Response::HTTP_UNAUTHORIZED);
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
            return $this->sendResponse($user, 'Profile Updated Successfully', Response::HTTP_OK);
        }

        return $this->sendError('Something went wrong', Response::HTTP_UNAUTHORIZED);
    }




    public function logout(Request $request)
    {
        $token = $request->user()->token();
        if ($token) {
            $token->revoke();
            return $this->sendResponse('', 'Logout Successfully', Response::HTTP_OK);
        } else {
            return $this->sendError('Something went wrong', Response::HTTP_UNAUTHORIZED);
        }
    }
}
