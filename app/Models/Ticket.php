<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Concerns\HasDepotScope;
use App\Models\Concerns\HasShopScope;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasDepotScope, HasFactory, HasShopScope;

    protected $fillable = [
        'reference',
        'shop_id',
        'depot_id',
        'customer_id',
        'device_id',
        'technicien_id',
        'status',
        'priority',
        'description',
        'diagnosis',
        'estimated_price',
        'tracking_token',
        'estimated_return_date',
        'closed_at',
        'created_by',
    ];

    protected $casts = [
        'estimated_return_date' => 'datetime',
        'closed_at' => 'datetime',
        'status' => TicketStatus::class,
        'priority' => TicketPriority::class,
        'created_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->tracking_token = Str::uuid();
            $m->reference = static::generateReference();
        });
    }

    public static function generateReference(): string
    {
        $year = now()->year;
        $count = static::withoutGlobalScopes()
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('SAV-%s-%05d', $year, $count);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function technicien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technicien_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->latest();
    }

    public function parts(): HasMany
    {
        return $this->hasMany(TicketPart::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function scopeFilter(Builder $q, array $filters): Builder
    {
        return $q
            ->when(
                $filters['search'] ?? null,
                fn ($q, $s) => $q->where(
                    fn ($q) => $q->where('reference', 'like', "%$s%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%$s%"))
                        ->orWhereHas(
                            'device',
                            fn ($q) => $q->where('brand', 'like', "%$s%")
                                ->orWhere('model', 'like', "%$s%")
                        )
                )
            )
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($filters['depot_id'] ?? null, fn ($q, $v) => $q->where('depot_id', $v))
            ->when($filters['technician_id'] ?? null, fn ($q, $v) => $q->where('technician_id', $v));
    }
}
