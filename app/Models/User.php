<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['shop_id', 'name', 'email', 'password', 'is_active', 'depot_active_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles,Notifiable;

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

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function depots(): BelongsToMany
    {
        return $this->belongsToMany(Depot::class, 'depot_user');
    }

    public function hasDepotAccess(Depot $depot): bool
    {
        if ($this->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        return $this->depots->contains($depot);
    }

    public function isAdminOrSuperAdmin(): bool
    {
        return $this->hasRole(['admin', 'super_admin']);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'technicien_id');
    }

    public function depotActive(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'depot_active_id');
    }

    public function requireDepotActive(): bool
    {
        return $this->depot_active_id !== null;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
