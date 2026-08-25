<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class InitiateBranchClosingRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:500'],
            'scheduled_date' => ['required', 'date', 'after:today'],
        ];
    }
}
