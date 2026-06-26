<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookPayload extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
