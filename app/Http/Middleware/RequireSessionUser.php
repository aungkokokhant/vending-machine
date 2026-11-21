<?php

namespace App\Http\Middleware;

use App\Contracts\UserRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSessionUser
{
    public function __construct(private UserRepository $users) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('user_id')) {
            return redirect()->guest(route('login'));
        }
        $user = $this->users->find((int) $request->session()->get('user_id'));
        if (! $user) {
            $request->session()->forget(['user_id', 'role', 'user_name']);

            return redirect()->guest(route('login'));
        }
        $request->session()->put(['role' => $user['role'], 'user_name' => $user['name']]);
        $request->attributes->set('current_user', $user);

        return $next($request);
    }
}
