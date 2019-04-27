<?php

namespace App\Http\Middleware;

use Closure;

class SignatureMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $headerName = 'X-Name')
    {
        // return $next($request);
        $response = $next($request);

        // Adding a CUSTOM HEADER to every response
        $response->headers->set($headerName, config('app.name'));

        return $response;
    }
}
