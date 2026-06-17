<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Delivery
    |--------------------------------------------------------------------------
    |
    | Flat delivery fee (in Naira) applied at checkout. Tiered delivery zones
    | are a planned enhancement; for MVP a single flat fee keeps it simple.
    |
    */

    'delivery_fee' => (float) env('GOBUY_DELIVERY_FEE', 1500),

];
