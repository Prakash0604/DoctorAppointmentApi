<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for managing Authentication"
 * )
 */
class AuthController extends Controller
{

  /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Register new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="Prakash Chaudhary"),
     *             @OA\Property(property="email", type="string", format="email", example="prakash@gmail.com"),
     *             @OA\Property(property="password", type="string", example="123456789"),
     *             @OA\Property(property="password_confirmation", type="string", example="123456789"),
     *             @OA\Property(property="role_id", type="integer", example="1"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User Register Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User Register Successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function register(AuthRequest $request)
    {
        $user = User::create($request->validated());
        if ($user) {
            return $this->sendResponse(['user' => $user], 'User registered successfully', Response::HTTP_CREATED);
        } else {
            return $this->sendError('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentication"},
     *     summary="Authenticate user and generate bearer token",
     *    @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="prakash@gmail.com"),
     *             @OA\Property(property="password", type="string", example="123456789"),
     *         ),
     *     ),
     *      @OA\Response(
     *         response=201,
     *         description="User LoggedIn",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User LoggedIn Successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Get(
     *     tags={"Authentication"},
     *     path="/api/v1/profile",
     *     summary="Get logged-in user details",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function profile()
    {
        $user =  Auth::user();
        if ($user) {
            return $this->sendResponse($user, 'Profile Information', Response::HTTP_OK);
        }
        return $this->sendError('Unauthenticated', Response::HTTP_UNAUTHORIZED);
    }

 /**
     * @OA\Post(
     *     path="/api/v1/profile/update",
     *     tags={"Authentication"},
     *     summary="Update the User profile",
     *     security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string", format="string", example="Sarimilaji"),
     *             @OA\Property(property="email", type="string", format="email", example="sarmilaji@gmail.com"),
     *             @OA\Property(property="profile_image", type="file", format="string", example="image.jpg"),
     *             @OA\Property(property="phone", type="integer" ,example="9800000000"),
     *             @OA\Property(property="address", type="string" ,example="Kathmandu-2 Lalitpur"),
     *             @OA\Property(property="gender", type="string" ,example="Male"),
     *             @OA\Property(property="dob", type="date" ,example="2059-06-04"),
     *         ),
     *     ),
     *      @OA\Response(
     *         response=201,
     *         description="Update Profile",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile Update"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
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


       /**
     * @OA\Post(
     *     tags={"Authentication"},
     *     path="/api/v1/logout",
     *     summary="Logout Session",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */


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
