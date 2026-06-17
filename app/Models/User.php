<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Customer\Models\WholesaleProfile;
use App\Modules\Order\Models\Order;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone', 'role', 'customer_type', 'wholesale_status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const TYPE_RETAIL = 'retail';

    public const TYPE_WHOLESALE = 'wholesale';

    public const ROLE_CUSTOMER = 'customer';

    public const WHOLESALE_NONE = 'none';

    public const WHOLESALE_PENDING = 'pending';

    public const WHOLESALE_APPROVED = 'approved';

    public const WHOLESALE_REJECTED = 'rejected';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function wholesaleProfile(): HasOne
    {
        return $this->hasOne(WholesaleProfile::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    /**
     * Wholesale pricing applies only once an application is approved
     * (approval flips customer_type to wholesale).
     */
    public function isWholesale(): bool
    {
        return $this->customer_type === self::TYPE_WHOLESALE;
    }

    public function hasPendingWholesaleApplication(): bool
    {
        return $this->wholesale_status === self::WHOLESALE_PENDING;
    }
}
