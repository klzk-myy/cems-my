<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class FinalizeBranchClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user !== null
            && ($user->role->value === UserRole::Admin->value
                || $user->role->value === UserRole::Manager->value);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'completed_at' => ['required', 'date'],
            'finalized_by' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
