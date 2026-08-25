<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TestDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isLocal()) {
            abort(404);
        }

        return $next($request);
    }
}
