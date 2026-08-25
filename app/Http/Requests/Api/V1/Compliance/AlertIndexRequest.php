<?php

namespace App\Http\Requests\Api\V1\Compliance;

use App\Http\Requests\ApiFormRequest;

class AlertIndexRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'priority' => 'nullable|in:critical,high,medium,low',
            'assigned' => 'nullable|in:yes,no',
            'status' => 'nullable|in:Open,Under_Review,Resolved,Escalated,Rejected',
        ];
    }
}
