<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'logo_url', 'address', 'plan_id', 'trial_ends_at', 'is_active',
    ];

    protected $casts = ['trial_ends_at' => 'datetime'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(User::class)->whereHas('roles', fn ($q) => $q->where('name', 'admin')
        );
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Abonnement en cours (actif ou en essai, non expiré). */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trial->value])
            ->where('ends_at', '>', now())
            ->latestOfMany();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function canAddDepot(): bool
    {
        $max = $this->plan?->max_depots;

        return $max === null || $this->depots()->count() < $max;
    }

    public function canAddUser(): bool
    {
        $max = $this->plan?->max_users;

        return $max === null || $this->users()->count() < $max;
    }

    /**
     * Vérifie que l'usage actuel de l'atelier (users/depots) rentre dans les
     * limites de l'offre visée, pour bloquer un changement d'offre qui
     * laisserait l'atelier hors-limites.
     */
    public function exceedsLimitsOf(Plan $plan): ?string
    {
        $userCount = $this->users()->count();

        if ($plan->max_users !== null && $userCount > $plan->max_users) {
            return "Vous avez {$userCount} utilisateurs, l'offre {$plan->name} est limitée à {$plan->max_users}.";
        }

        $depotCount = $this->depots()->count();

        if ($plan->max_depots !== null && $depotCount > $plan->max_depots) {
            return "Vous avez {$depotCount} dépôts, l'offre {$plan->name} est limitée à {$plan->max_depots}.";
        }

        return null;
    }

    /**
     * Logo encodé en data URI, pour l'intégrer dans un PDF (dompdf ne peut pas
     * charger les fichiers via une URL HTTP locale).
     */
    public function logoBase64(): ?string
    {
        if (! $this->logo_url) {
            return null;
        }

        $path = str_replace('/storage/', '', $this->logo_url);

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path);
        $contents = Storage::disk('public')->get($path);

        return "data:{$mime};base64,".base64_encode($contents);
    }
}
