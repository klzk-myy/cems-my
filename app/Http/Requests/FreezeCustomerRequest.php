<?php

namespace App\Http\Requests;

class FreezeCustomerRequest extends AuthorizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // pd-00 freeze actions must carry an auditable justification.
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
