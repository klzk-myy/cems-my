<?php

namespace App\Modules\Pos\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PosRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rates' => 'required|array|min:1',
            'rates.*.buy' => 'required|numeric|min:0|max:999999.999999',
            'rates.*.sell' => 'required|numeric|min:0|max:999999.999999',
            'rates.*.mid' => 'required|numeric|min:0|max:999999.999999',
        ];
    }
}
