<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public const SESSION_KEY = 'momenkita_admin';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get(self::SESSION_KEY)) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
