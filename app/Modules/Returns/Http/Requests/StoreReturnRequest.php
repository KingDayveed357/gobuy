<?php

namespace App\Modules\Returns\Http\Requests;

use App\Modules\Returns\Enums\RefundDestination;
use App\Modules\Returns\Enums\ReturnReason;
use App\Modules\Returns\Services\ReturnRequestService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason_code' => ['required', Rule::enum(ReturnReason::class)],
            'refund_destination' => ['required', Rule::enum(RefundDestination::class)],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.selected' => ['nullable'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.condition_reported' => ['nullable', 'in:unopened,opened,damaged'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'max:5120'], // 5MB each
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $reason = ReturnReason::tryFrom((string) $this->input('reason_code'));

            if ($reason?->requiresPhoto() && ! $this->hasFile('photos')) {
                $validator->errors()->add('photos', 'Please add at least one photo for this reason.');
            }
        });
    }

    /**
     * Selected return lines for {@see ReturnRequestService}.
     * Unchecked rows are dropped.
     *
     * @return array<int, array{order_item_id: int, quantity: int, condition_reported: ?string}>
     */
    public function lines(): array
    {
        return collect($this->validated('items'))
            ->filter(fn (array $row) => ! empty($row['selected']))
            ->map(fn (array $row) => [
                'order_item_id' => (int) $row['order_item_id'],
                'quantity' => (int) $row['quantity'],
                'condition_reported' => $row['condition_reported'] ?? null,
            ])
            ->values()
            ->all();
    }
}
