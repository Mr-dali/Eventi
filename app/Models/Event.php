<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'titre', 'description', 'date_evenement', 'lieu', 'capacite',
        'prix', 'image', 'categorie', 'statut', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_evenement' => 'datetime',
            'prix'           => 'decimal:3',
            'created_at'     => 'datetime',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Ajoute places_prises (somme des réservations confirmées) et nb_resa (leur nombre). */
    public function scopeWithPlaces(Builder $query): Builder
    {
        $occupied = function ($query): void {
            $query->where('statut', 'confirmee')
                ->orWhere(function ($query): void {
                    $query->where('statut', 'en_attente')
                        ->where('expires_at', '>', now());
                });
        };

        return $query
            ->withSum(['reservations as places_prises' => $occupied], 'nb_places')
            ->withCount(['reservations as nb_resa' => $occupied]);
    }

    public function getPlacesPrisesAttribute($value): int
    {
        return (int) $value;
    }

    public function getPlacesRestantesAttribute(): int
    {
        return $this->capacite - $this->places_prises;
    }
}
