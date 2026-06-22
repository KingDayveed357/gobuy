<?php

namespace App\Modules\Customer\Models;

use App\Models\User;
use App\Modules\Customer\Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'line1',
        'line2',
        'city',
        'state',
        'country',
        'postal_code',
        'is_default_shipping',
        'is_default_billing',
    ];

    protected function casts(): array
    {
        return [
            'is_default_shipping' => 'boolean',
            'is_default_billing' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** One-line, human-readable address. */
    public function formatted(): string
    {
        return collect([$this->line1, $this->line2, $this->city, $this->state, $this->country])
            ->filter()
            ->implode(', ');
    }

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }
}
