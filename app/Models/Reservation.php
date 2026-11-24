<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event_id',
        'nb_places',
        'statut',
        'stripe_checkout_session_id',
        'stripe_checkout_url',
        'stripe_payment_intent_id',
        'stripe_amount_cents',
        'stripe_tnd_per_usd',
        'payment_status',
        'expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'stripe_amount_cents' => 'integer',
            'stripe_tnd_per_usd' => 'decimal:6',
        ];
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
