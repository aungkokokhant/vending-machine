<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->is('api/*') ? $request->attributes->get('current_user')['role'] : $request->session()->get('role');
        abort_unless($role === 'admin', 403, 'Administrator access is required.');

        return $next($request);
    }
}
