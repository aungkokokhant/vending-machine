<?php

namespace App\Http\Middleware;

use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiToken
{
    public function __construct(private TokenService $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->tokens->authenticate($request->bearerToken() ?? '');
        if (! $user) {
            return response()->json(['message' => 'A valid, unexpired Bearer token is required.'], 401, ['WWW-Authenticate' => 'Bearer']);
        }
        $request->attributes->set('current_user', $user);

        return $next($request);
    }
}
