<?php

namespace App\Http\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;

/**
 * Single source of branch-scoped access control.
 *
 * The web controllers and API controllers diverged on the same rule:
 * admin (and, via role:canManageAllBranches, managers with "manage all") see
 * everything; all other users are restricted to their assigned branch.
 *
 * Two idioms were in circulation:
 *   $query->when(! $user->role->canManageAllBranches(), ...)
 *   if ($user && $user->branch_id !== null) { $query->where(...) }
 *
 * This trait exposes one rule via multiple helper shapes so every caller
 * looks the same without forcing an API change on the consuming controller.
 */
trait BranchScopedQuery
{
    /**
     * Return the authenticated user's branch_id, or null if they may view
     * everything (admin / "manage all branches").
     */
    protected function currentUserBranchId(): ?int
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        if ($user->role->canManageAllBranches()) {
            return null;
        }

        return $user->branch_id;
    }

    /**
     * Apply branch scoping to a query builder.
     */
    protected function scopeByBranch(Builder $query, ?int $branchId = null): Builder
    {
        $branchId = $branchId ?? $this->currentUserBranchId();

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    /**
     * Apply branch scoping to a Collection / Enumerable.
     */
    protected function filterByBranch(Enumerable $collection, ?int $branchId = null): Enumerable
    {
        $branchId = $branchId ?? $this->currentUserBranchId();

        if ($branchId !== null) {
            return $collection->filter(fn ($item) => (int) ($item instanceof Model ? $item->branch_id : $item['branch_id']) === (int) $branchId);
        }

        return $collection;
    }

    /**
     * Determine whether a model belongs to the current user's branch.
     *
     * Returns true for admins / "manage all" users, or when the model's
     * branch_id matches the user's branch_id.
     */
    protected function belongsToCurrentUserBranch(Model $model): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($user->role->canManageAllBranches()) {
            return true;
        }

        return (int) $model->branch_id === (int) $user->branch_id;
    }
}
