<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * Domaines de permission qu'il est possible de désactiver pour un plan.
     * Liste blanche volontairement restreinte : settings/users/dashboard ne
     * doivent jamais pouvoir être désactivés (un atelier resterait bloqué
     * hors de son propre panneau d'administration).
     *
     * @var array<int, string>
     */
    public const DISABLEABLE_MODULES = ['tickets','inventaire'];

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'max_users', 'max_depots', 'features', 'disabled_modules', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'features' => 'array',
        'disabled_modules' => 'array',
        'is_active' => 'boolean',
    ];

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class);
    }

    /**
     * Détermine si ce plan désactive le module dont relève la permission
     * donnée (ex: "tickets.view" relève du module "tickets").
     */
    public function disablesPermissionDomain(string $permission): bool
    {
        $domain = strstr($permission, '.', true) ?: $permission;

        // Recoupe avec la liste blanche même si disabled_modules contient une
        // valeur inattendue (ex: donnée insérée hors du formulaire validé) :
        // un atelier ne doit jamais pouvoir se retrouver bloqué hors de ses
        // propres réglages.
        $disabled = array_intersect($this->disabled_modules ?? [], self::DISABLEABLE_MODULES);

        return in_array($domain, $disabled, true);
    }
}
