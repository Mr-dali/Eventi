<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'event_id', 'nb_places', 'statut'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getUserNameAttribute(): string
    {
        return $this->user->prenom . ' ' . $this->user->nom;
    }

    public function getEventTitreAttribute(): string
    {
        return $this->event->titre;
    }
}
