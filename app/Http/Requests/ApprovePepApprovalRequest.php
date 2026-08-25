<?php

namespace App\Http\Requests;

class ApprovePepApprovalRequest extends AuthorizedFormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the role:manager,admin route middleware;
        // self-approval is blocked in PepApprovalService::approve().
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
