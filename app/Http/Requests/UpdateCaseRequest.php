<?php

namespace App\Http\Requests;

use App\Enums\ComplianceCasePriority;
use Illuminate\Validation\Rules\Enum as EnumRule;

class UpdateCaseRequest extends AuthorizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => ['nullable', new EnumRule(ComplianceCasePriority::class)],
            'case_summary' => 'nullable|string|max:1000',
        ];
    }
}
