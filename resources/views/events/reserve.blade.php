@extends('layouts.app')

@section('title')
Réserver — {{ $event['titre'] }}
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reserve.css') }}">
@endpush

@section('content')
@php $success = session('reserved'); $error = $errors->first(); @endphp
<nav>
    <div class="nav-logo">Eventi</div>
    <div style="display:flex;gap:14px;align-items:center;">
        <a href="{{ route('notifications.index') }}">Notifications ({{ auth()->user()->unreadNotifications()->count() }})</a>
        <a href="{{ route('profile.edit') }}">Mon profil</a>
        <a href="{{ route('events.index') }}" class="nav-back">← Retour aux événements</a>
    </div>
</nav>

<div class="page">

    <!-- INFOS ÉVÉNEMENT -->
    <div class="event-info">
        <div class="event-tag">Réservation</div>
        <h1 class="event-title">{{ $event['titre'] }}</h1>

        <div class="event-meta-list">
            <div class="meta-row">
                <div class="meta-icon">📅</div>
                <div>
                    <strong>{{ ucfirst($event->date_evenement->translatedFormat('l d F Y')) }}</strong>
                    <span>à {{ $event->date_evenement->format('H\hi') }}</span>
                </div>
            </div>
            <div class="meta-row">
                <div class="meta-icon">📍</div>
                <div>
                    <strong>{{ $event['lieu'] }}</strong>
                    <span>Lieu de l'événement</span>
                </div>
            </div>
            <div class="meta-row">
                <div class="meta-icon">👥</div>
                <div>
                    <strong>{{ $places_restantes }} places restantes</strong>
                    <span>sur {{ $event['capacite'] }} au total</span>
                </div>
            </div>
        </div>

        @if($event['description'])
        <div class="desc-box">
            <h3>À propos de cet événement</h3>
            <p>{{ nl2br(htmlspecialchars($event['description'])) }}</p>
        </div>
        @endif
    </div>

    <!-- FORMULAIRE -->
    <div class="form-card">
        @if($success)
            <div class="success-state">
                <div class="success-icon">✓</div>
                <h3>Réservation confirmée !</h3>
                <p>
                    Vous avez réservé <strong style="color:var(--text)">{{ $success }} place{{ $success > 1 ? 's' : '' }}</strong>
                    pour <strong style="color:var(--text)">{{ $event['titre'] }}</strong>.
                </p>
                <a href="{{ route('events.index') }}" class="btn-back">← Voir tous les événements</a>
            </div>

        @elseif($dejaRese && $reservation && $reservation->statut === 'en_attente')
            <div class="success-state">
                <h3>Paiement en attente</h3>
                <p>Votre réservation de {{ $reservation->nb_places }} place(s) est gardée jusqu’à l’expiration de la session de paiement.</p>
                @if($checkoutUrl)
                    <a href="{{ $checkoutUrl }}" class="btn-confirm">Continuer le paiement sur Stripe</a>
                @else
                    <p>La session Stripe est en cours de création. Actualisez cette page dans quelques instants.</p>
                @endif
                @if(session('payment_cancelled'))
                    <p class="error-box">Le paiement n’a pas été finalisé. Vous pouvez reprendre la session ci-dessus.</p>
                @endif
            </div>

        @elseif($dejaRese)
            <div class="success-state">
                <div class="success-icon">✓</div>
                <h3>Déjà réservé !</h3>
                <p>Vous avez déjà une réservation pour cet événement.</p>
                <a href="{{ route('events.index') }}" class="btn-back">← Retour aux événements</a>
            </div>

        @elseif($places_restantes <= 0)
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:48px;margin-bottom:16px;">😔</div>
                <h3 style="font-family:'Syne',sans-serif;margin-bottom:8px;">Événement complet</h3>
                <p style="color:var(--muted);font-size:14px;margin-bottom:24px;">Il n'y a plus de places disponibles.</p>
                <a href="{{ route('events.index') }}" class="btn-back">← Retour aux événements</a>
            </div>

        @else
            <div class="price-display">
                {{ number_format($event['prix'], 3, '.', '') }} <small>TND</small>
            </div>
            <p class="price-sub">par personne</p>
            @if((float) $event['prix'] > 0 && $usdUnitCents)
                <p class="price-sub">
                    Environ {{ number_format($usdUnitCents / 100, 2, '.', '') }} USD par place
                    (taux : 1 USD = {{ number_format($tndPerUsd, 4, '.', '') }} TND)
                </p>
            @endif

            @if($error)
                <div class="error-box">⚠ {{ $error }}</div>
            @endif

            @if((float) $event['prix'] > 0 && ! $canPay)
                <div class="error-box">Le paiement Stripe n’est pas encore configuré. La réservation payante est temporairement indisponible.</div>
            @endif

            <form method="POST" action="{{ route('events.reserve.store', $event) }}" id="reserveForm">
@csrf
                <label>Nombre de places</label>
                <div class="qty-control">
                    <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                    <input type="number" name="nb_places" id="nb_places"
                           class="qty-input"
                           value="1" min="1" max="{{ $places_restantes }}"
                           oninput="updateTotal()">
                    <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                </div>

                <div class="total-row">
                    <span class="total-label">Total à payer</span>
                    <span class="total-val" id="totalDisplay">
                        {{ number_format($event['prix'], 3, '.', '') }} TND
                        @if((float) $event['prix'] > 0 && $usdUnitCents)
                            (≈ {{ number_format($usdUnitCents / 100, 2, '.', '') }} USD)
                        @endif
                    </span>
                </div>

                <button type="submit" class="btn-confirm" @disabled(! $canPay)>
                    {{ (float) $event['prix'] > 0 ? 'Payer en USD avec Stripe' : 'Confirmer la réservation gratuite' }}
                </button>
                <p class="places-info">Il reste {{ $places_restantes }} place{{ $places_restantes > 1 ? 's' : '' }} disponible{{ $places_restantes > 1 ? 's' : '' }}</p>
            </form>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
const prixUnit = {{ (float)$event['prix'] }};
const usdUnit = @json($usdUnitCents !== null ? $usdUnitCents / 100 : null);
const maxPlaces = {{ $places_restantes }};

function changeQty(delta) {
    const input = document.getElementById('nb_places');
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(maxPlaces, val));
    input.value = val;
    updateTotal();
}

function updateTotal() {
    const nb = Math.max(1, Math.min(maxPlaces, parseInt(document.getElementById('nb_places').value) || 1));
    document.getElementById('nb_places').value = nb;
    const total = (prixUnit * nb).toFixed(3);
    const usdTotal = usdUnit === null ? '' : ` (≈ ${(usdUnit * nb).toFixed(2)} USD)`;
    document.getElementById('totalDisplay').textContent = total + ' TND' + usdTotal;
}
</script>
@endpush
