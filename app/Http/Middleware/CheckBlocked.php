<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->bloqueado) {
            return response()->json([
                'message' => 'Sua conta está bloqueada. Entre em contato com o suporte.',
            ], 403);
        }

        return $next($request);
    }
}
