<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && (string) auth()->user()->role === '1' && (bool) auth()->user()->status) {
            return $next($request);
        }

        abort(403, 'Akses hanya untuk admin.');
    }
}
