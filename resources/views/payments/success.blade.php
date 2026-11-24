@extends('layouts.app')

@section('title', 'Résultat du paiement')

@section('content')
<main class="container py-5">
    @if($reservation->statut === 'confirmee' && $reservation->payment_status === 'paid')
        <h1>Paiement confirmé</h1>
        <p>Votre réservation pour « {{ $reservation->event->titre }} » est confirmée.</p>
    @elseif($reservation->payment_status === 'review')
        <h1>Paiement reçu, vérification en cours</h1>
        <p>Le paiement a été reçu, mais la disponibilité des places doit être vérifiée. Contactez l’organisateur si nécessaire.</p>
    @else
        <h1>Paiement en cours de confirmation</h1>
        <p>Stripe a traité votre paiement. La réservation sera confirmée après réception du webhook sécurisé.</p>
    @endif

    <a href="{{ route('events.index') }}">Retour aux événements</a>
</main>
@endsection
