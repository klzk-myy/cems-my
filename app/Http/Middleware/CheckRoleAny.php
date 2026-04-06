<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware that allows access if user has ANY of the specified roles.
 * Unlike CheckRole which requires ALL roles, this middleware requires ANY.
 */
class CheckRoleAny
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Role checks to perform (OR logic - user needs ANY one)
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check if user has ANY of the required roles
        foreach ($roles as $role) {
            $hasRole = match ($role) {
                'admin' => $user->isAdmin(),
                'manager' => $user->isManager(),
                'compliance' => $user->isComplianceOfficer(),
                'compliance_officer' => $user->isComplianceOfficer(),
                'teller' => true, // All authenticated users are at least tellers
                default => false,
            };

            if ($hasRole) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized. You do not have permission to access this resource.');
    }
}
