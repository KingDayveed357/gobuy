<?php

namespace App\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
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
            'mode' => ['required', Rule::in(['adjust', 'set'])],
            // For "adjust" this is a signed delta; for "set" an absolute target.
            'amount' => ['required', 'integer', $this->input('mode') === 'set' ? 'min:0' : 'between:-1000000,1000000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
