<?php

namespace App\Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WholesaleApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'rc_number' => ['nullable', 'string', 'max:50'],
            'business_phone' => ['required', 'string', 'max:30'],
            'business_address' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'intent' => ['required', 'string', 'max:1000'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
