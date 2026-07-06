<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasShopScope, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_ttc', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('invoice');
    }

    protected $fillable = [
        'number', 'shop_id', 'ticket_id', 'customer_id',
        'status', 'total_ht', 'tax_rate', 'tax_amount',
        'total_ttc', 'notes', 'issued_at', 'due_at', 'paid_at',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->number = static::generateNumber());
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $count = static::withoutGlobalScopes()
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('FAC-%s-%05d', $year, $count);
    }

    public function recalculate(): void
    {
        $ht = $this->lines->sum('total');
        $tax = round($ht * ($this->tax_rate / 100), 2);

        $this->update([
            'total_ht' => $ht,
            'tax_amount' => $tax,
            'total_ttc' => $ht + $tax,
        ]);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
