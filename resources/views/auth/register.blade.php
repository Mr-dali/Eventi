@extends('layouts.app')

@section('title')
Inscription — Eventi
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endpush

@section('content')
    <div class="card">
        <div class="logo">Eventi</div>
        <p class="subtitle">Créez votre compte gratuitement</p>

        @if($errors->any())
            <div class="error-box">&#9888; {{ $errors->first() }}</div>
        @endif

        @if(session('success'))
            <div class="success-box">
                &#10003; {{ session('success') }}
                <br><a href="{{ route('login') }}" style="color:var(--success); font-weight:500;">Se connecter maintenant &rarr;</a>
            </div>
        @endif

        @if(!session('success'))
        <form method="POST" action="{{ route('register') }}">
@csrf
            <div class="row-2">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom"
                        placeholder="Ben Ali"
                        value="{{ old('nom', '') }}"
                        required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom"
                        placeholder="Amine"
                        value="{{ old('prenom', '') }}"
                        required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                    placeholder="vous@exemple.tn"
                    value="{{ old('email', '') }}"
                    required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password"
                    placeholder="••••••••" required autocomplete="new-password">
                <p class="hint">Au moins 8 caractères</p>
            </div>

            <div class="form-group">
                <label for="confirm">Confirmer le mot de passe</label>
                <input type="password" id="confirm" name="confirm"
                    placeholder="••••••••" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn">Créer mon compte</button>
        </form>
        @endif

        <div class="link-row">
            Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a>
        </div>
    </div>
@endsection
