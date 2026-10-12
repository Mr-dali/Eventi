<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    const UPDATED_AT = null;

    // "role" n'est volontairement pas fillable : il s'assigne explicitement.
    protected $fillable = ['nom', 'prenom', 'email', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'created_at' => 'datetime'];
    }

    // Pas de colonne remember_token dans ce schéma.
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // Alias renseigné par withSum(... as places_total)
    public function getPlacesTotalAttribute($value): int
    {
        return (int) $value;
    }
}
