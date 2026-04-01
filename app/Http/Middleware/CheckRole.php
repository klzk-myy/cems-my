<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has the required role
        $hasRole = match($role) {
            'admin' => $user->isAdmin(),
            'manager' => $user->isManager(),
            'compliance' => $user->isComplianceOfficer(),
            'teller' => true, // All authenticated users are at least tellers
            default => false,
        };

        if (!$hasRole) {
            abort(403, 'Unauthorized. You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
