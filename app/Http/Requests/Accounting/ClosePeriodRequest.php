<?php

namespace App\Http\Requests\Accounting;

use App\Http\Requests\AuthorizedFormRequest;

class ClosePeriodRequest extends AuthorizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'integer', 'exists:accounting_periods,id'],
            'closure_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
