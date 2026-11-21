<?php

namespace App\Http\Controllers;

use App\Contracts\UserRepository;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private UserRepository $users) {}

    public function show(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $this->users->findByEmail($request->validated('email'));
        if (! $user || ! Hash::check($request->validated('password'), $user['password'])) {
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.']);
        }
        $request->session()->regenerate();
        $request->session()->put(['user_id' => $user['id'], 'role' => $user['role'], 'user_name' => $user['name']]);

        return redirect()->intended(route('products.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
