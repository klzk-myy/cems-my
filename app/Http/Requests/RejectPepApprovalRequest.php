<?php

namespace App\Http\Requests;

class RejectPepApprovalRequest extends AuthorizedFormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the role:manager,admin route middleware;
        // self-approval is blocked in PepApprovalService::reject().
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:5000',
        ];
    }
}
