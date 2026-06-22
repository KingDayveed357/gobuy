<?php

namespace App\Modules\Pricing\Http\Requests;

use App\Modules\Pricing\Services\BulkPricingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage_products') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'field' => ['required', Rule::in(BulkPricingService::FIELDS)],
            'direction' => ['required', 'in:increase,decrease'],
            'method' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0.01', $this->input('method') === 'percentage' ? 'max:100' : 'max:100000000'],
            'reason' => ['nullable', 'string', 'max:160'],
        ];
    }
}
