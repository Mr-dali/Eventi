@extends('layouts.app')

@section('title', 'Mon profil — Eventi')

@push('styles')
<style>
    .account-page { max-width: 720px; margin: 40px auto; padding: 0 20px; }
    .account-nav { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
    .account-card { padding: 28px; border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; }
    .account-card label { display: block; margin: 16px 0 6px; font-weight: 600; }
    .account-card input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; }
    .account-card button { margin-top: 20px; padding: 10px 16px; border: 0; border-radius: 8px; cursor: pointer; }
    .account-error, .account-success { margin: 12px 0; padding: 12px; border-radius: 8px; }
    .account-error { background: #fef2f2; color: #991b1b; }
    .account-success { background: #ecfdf5; color: #065f46; }
</style>
@endpush

@section('content')
<main class="account-page">
    <nav class="account-nav">
        <a href="{{ route('events.index') }}">Événements</a>
        <a href="{{ route('notifications.index') }}">Notifications ({{ auth()->user()->unreadNotifications()->count() }})</a>
    </nav>

    <section class="account-card">
        <h1>Mon profil</h1>
        @if(session('success'))
            <div class="account-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="account-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <label for="prenom">Prénom</label>
            <input id="prenom" name="prenom" value="{{ old('prenom', auth()->user()->prenom) }}" required maxlength="100">

            <label for="nom">Nom</label>
            <input id="nom" name="nom" value="{{ old('nom', auth()->user()->nom) }}" required maxlength="100">

            <label for="email">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required maxlength="150">

            <hr>
            <h2>Changer le mot de passe</h2>
            <label for="current_password">Mot de passe actuel</label>
            <input id="current_password" type="password" name="current_password" autocomplete="current-password">

            <label for="password">Nouveau mot de passe</label>
            <input id="password" type="password" name="password" autocomplete="new-password">

            <label for="password_confirmation">Confirmer le nouveau mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">

            <button type="submit">Enregistrer les modifications</button>
        </form>
    </section>
</main>
@endsection
