<?php

namespace App\Modules\Pricing\Concerns;

use App\Modules\Pricing\Models\PriceHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records an immutable {@see PriceHistory} row whenever a priced field on the
 * model changes. Apply to any model exposing money columns; declare which
 * fields to watch via a public static $pricedFields array (defaults to the
 * common variant fields).
 */
trait RecordsPriceHistory
{
    public static function bootRecordsPriceHistory(): void
    {
        static::updating(function (Model $model): void {
            $fields = property_exists($model, 'pricedFields')
                ? $model::$pricedFields
                : ['retail_price', 'sale_price', 'wholesale_price'];

            foreach ($fields as $field) {
                if (! $model->isDirty($field)) {
                    continue;
                }

                $old = $model->getRawOriginal($field);
                $new = $model->getAttributes()[$field] ?? null;

                PriceHistory::create([
                    'priceable_type' => $model->getMorphClass(),
                    'priceable_id' => $model->getKey(),
                    'field' => $field,
                    'old_value' => $old !== null ? (int) $old : null,
                    'new_value' => $new !== null ? (int) $new : null,
                    'admin_id' => Auth::guard('admin')->id(),
                    'reason' => property_exists($model, 'priceChangeReason') ? $model->priceChangeReason : null,
                ]);
            }
        });
    }
}
