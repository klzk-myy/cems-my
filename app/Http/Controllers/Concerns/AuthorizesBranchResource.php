<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Branch-level authorization. Admins may access any branch; other roles
 * are restricted to their own branch. Resources without a branch_id
 * (null) are denied for non-admins - only admins may act on them.
 *
 * Two accessors:
 *   - `authorizeBranchAccess(int $branchId)` — when only the ID is available.
 *   - `authorizeBranchResource(Model $resource, ...)` — when you have the
 *     full model (looks up `branch_id` via `getAttribute`, or the primary key
 *     if the resource is a `Branch` itself).
 *
 * Both return a 403 `JsonResponse` when unauthorized, or a truthy value
 * (`true` / the `$resource`) when authorized.
 */
trait AuthorizesBranchResource
{
    protected function authorizeBranchAccess(int $branchId): ?JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return $this->denyResponse('Unauthenticated.', 401);
        }

        if ($user->isAdmin()) {
            return null;
        }

        if ((int) $branchId !== (int) $user->branch_id) {
            return $this->denyResponse('You do not have permission to access this branch.', 403);
        }

        return null;
    }

    /**
     * @return true|JsonResponse True when authorized, a 403/401 response otherwise.
     */
    protected function authorizeBranchResource(
        Model $resource,
        string $action = 'access',
        ?string $message = null
    ): true|JsonResponse {
        $user = Auth::user();

        if ($user === null) {
            return $this->denyResponse('Unauthenticated.', 401);
        }

        if ($user->isAdmin()) {
            return true;
        }

        $resourceBranchId = $resource instanceof Branch
            ? $resource->getKey()
            : $resource->getAttribute('branch_id');

        // Legacy rows predating branch scoping carry a null branch_id and
        // therefore have no provable branch ownership: deny them for
        // non-admins instead of silently granting access to everyone.
        // (Admins were already allowed above.)
        if ($resourceBranchId === null) {
            return $this->denyResponse(
                $message ?? "You can only {$action} resources for your own branch.",
                403
            );
        }

        if ((int) $resourceBranchId !== (int) $user->branch_id) {
            return $this->denyResponse(
                $message ?? "You can only {$action} resources for your own branch.",
                403
            );
        }

        return true;
    }

    private function denyResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => [],
        ], $status);
    }
}
