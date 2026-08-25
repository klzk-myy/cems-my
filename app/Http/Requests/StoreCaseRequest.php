<?php

namespace App\Http\Requests;

use App\Enums\ComplianceCaseType;
use App\Enums\FindingSeverity;
use Illuminate\Validation\Rules\Enum as EnumRule;

class StoreCaseRequest extends AuthorizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'finding_id' => 'nullable|exists:compliance_findings,id',
            'case_type' => ['required', new EnumRule(ComplianceCaseType::class)],
            'assigned_to' => 'required|exists:users,id',
            'summary' => 'nullable|string|max:1000',
            'customer_id' => ['nullable', 'required_without:finding_id', 'exists:customers,id'],
            'severity' => ['nullable', new EnumRule(FindingSeverity::class)],
        ];
    }
}
