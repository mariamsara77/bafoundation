<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'donation_category_id', 'tran_id', 'session_key', 'gateway',
    'payment_method', 'amount', 'currency', 'status', 'donor_name',
    'donor_email', 'donor_phone', 'message', 'validation_id', 'bank_tran_id',
    'card_type', 'card_brand', 'card_issuer', 'store_amount', 'paid_at',
    'failed_at', 'cancelled_at', 'gateway_payload',
])]
class Donation extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DonationCategory::class, 'donation_category_id');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'store_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }
}