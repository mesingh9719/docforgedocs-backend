<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected \App\Services\AuthService $authService,
        protected \App\Services\BusinessService $businessService
    ) {
    }

    /**
     * Register a new user.
     *
     * @param \App\Http\Requests\Api\V1\RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(\App\Http\Requests\Api\V1\RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        \App\Services\ActivityLogger::log(
            'user.register',
            'New user registration: ' . $result['user']->name,
            'info',
            ['email' => $result['user']->email],
            $result['user']->id
        );

        return response()->json([
            'message' => 'User registered successfully.',
            'data' => new \App\Http\Resources\Api\V1\UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    /**
     * Login a user.
     *
     * @param \App\Http\Requests\Api\V1\LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(\App\Http\Requests\Api\V1\LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        \App\Services\ActivityLogger::log(
            'user.login',
            'User logged in',
            'info',
            ['ip' => $request->ip()],
            $result['user']->id
        );

        return response()->json([
            'message' => 'Login successful.',
            'data' => new \App\Http\Resources\Api\V1\UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    /**
     * Logout a user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $this->authService->logout($user);

        if ($user) {
            \App\Services\ActivityLogger::log('user.logout', 'User logged out', 'info', [], $user->id);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Update business information.
     *
     * @param \App\Http\Requests\Api\V1\BusinessUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function businessUpdate(\App\Http\Requests\Api\V1\BusinessUpdateRequest $request)
    {
        $business = $this->businessService->updateBusiness(
            $request->validated()['business_id'],
            $request->validated()
        );

        return response()->json([
            'message' => 'Business updated successfully.',
            'data' => new \App\Http\Resources\Api\V1\BusinessResource($business),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
        }
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => new \App\Http\Resources\Api\V1\UserResource($user),
        ]);
    }
}
