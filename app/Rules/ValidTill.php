<?php

namespace App\Rules;

use App\Enums\CounterStatus;
use App\Models\Counter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTill implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail("The {$attribute} must be a string.");

            return;
        }

        $user = auth()->user();

        // An authenticated user without a branch assignment cannot use any till.
        if ($user !== null && $user->branch_id === null) {
            $fail("The selected {$attribute} could not be validated without an assigned branch.");

            return;
        }

        // Scope to the user's branch so a till cannot book against another
        // branch's balances. Without an authenticated user (CLI/test context)
        // fall back to the unscoped lookup; HTTP routes are auth-guarded.
        $counterQuery = Counter::query()
            ->where(function ($query) use ($value) {
                $query->where('code', $value)->orWhere('id', $value);
            });

        if ($user !== null && $user->branch_id !== null) {
            $counterQuery->where('branch_id', $user->branch_id);
        }

        $counter = $counterQuery->first();

        if ($counter === null) {
            $fail("The selected {$attribute} is invalid.");

            return;
        }

        if ($counter->status !== CounterStatus::Active) {
            $fail("The selected {$attribute} is not open.");
        }
    }
}
