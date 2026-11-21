<?php

namespace App\Http\Controllers\Api;

use App\Contracts\UserRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private UserRepository $users, private TokenService $tokens) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->users->findByEmail($request->validated('email'));
        if (! $user || ! Hash::check($request->validated('password'), $user['password'])) {
            return response()->json(['message' => 'The email or password is incorrect.'], 401);
        }

        return response()->json([...$this->tokens->issue($user['id']), 'user' => array_intersect_key($user, array_flip(['id', 'name', 'email', 'role']))])->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokens->revoke($request->bearerToken());

        return response()->json(['message' => 'Token revoked.']);
    }
}
