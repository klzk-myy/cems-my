<?php

namespace App\Http\Requests;

use App\Enums\ComplianceCaseStatus;
use Illuminate\Validation\Rules\Enum as EnumRule;

class UpdateCaseStatusRequest extends AuthorizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new EnumRule(ComplianceCaseStatus::class)],
            'notes' => 'nullable|string',
        ];
    }
}
