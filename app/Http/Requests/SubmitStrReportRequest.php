<?php

namespace App\Http\Requests;

class SubmitStrReportRequest extends AuthorizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The reference issued by BNM FIED when the report is lodged.
            'bnm_reference' => ['required', 'string', 'max:100'],
        ];
    }
}
