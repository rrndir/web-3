<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            auth()->check()
            && (string) auth()->user()->role === '2'
            && (bool) auth()->user()->status
        ) {
            return $next($request);
        }

        if (auth()->check()) {
            return redirect()->route('beranda')->with('msgError', 'Fitur ini hanya tersedia untuk customer.');
        }

        $refererPath = parse_url($request->headers->get('referer', ''), PHP_URL_PATH);
        $redirectPath = $request->isMethod('get') ? '/' . ltrim($request->path(), '/') : $refererPath;
        if (!is_string($redirectPath) || $redirectPath === '' || str_starts_with($redirectPath, '//')) {
            $redirectPath = route('order.cart', absolute: false);
        }

        return redirect()->route('customer.login', ['redirect' => $redirectPath])
            ->with('msgError', 'Silakan masuk atau daftar sebagai customer untuk melanjutkan.');
    }
}
