<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $isAdmin = $user->role?->canManageAllBranches() ?? false;

            // Deny-by-default: an authenticated non-admin without a branch
            // assignment cannot prove which branch's data they may touch,
            // so they must not pass through unchecked.
            if (! $isAdmin && ! $user->branch_id) {
                abort(403, 'You do not have permission to access resources for this branch.');
            }

            $requestedBranchId = $request->route('branch')
                ?? $request->route('branchId')
                ?? $request->route('branch_id')
                ?? $request->input('branch_id');

            if ($requestedBranchId !== null
                && ! $isAdmin
                && (int) $requestedBranchId !== (int) $user->branch_id) {
                abort(403, 'You do not have permission to access resources for this branch.');
            }

            // Always publish the caller's scope downstream - even when no
            // branch identifier was present in the request. Consumers such as
            // Api/V1/CustomerController::index read _branch_scope.
            $request->merge(['_branch_scope' => $user->branch_id]);
        }

        return $next($request);
    }
}
