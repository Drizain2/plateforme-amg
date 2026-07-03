<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id', 'subscription_id', 'plan_id', 'billing_period',
        'amount', 'currency', 'reference', 'status',
        'gateway', 'gateway_payment_id', 'gateway_response',
        'notes', 'validated_by', 'validated_at', 'rejected_reason', 'rejected_at',
    ];

    protected $casts = [
        'billing_period' => BillingPeriod::class,
        'status' => PaymentStatus::class,
        'gateway_response' => 'array',
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
