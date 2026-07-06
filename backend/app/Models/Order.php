<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_id',
        'paypal_order_status',
        'capture_id',
        'capture_status',
        'payer_name',
        'payer_email',
        'item_number',
        'title',
        'price',
        'currency',
        'paid_at',
        'mail_sent_at',
        'mail_error',
        'raw_payload',
        'paypal_payload',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'paid_at' => 'datetime',
        'mail_sent_at' => 'datetime',
        'raw_payload' => 'array',
        'paypal_payload' => 'array',
    ];
}
