@extends('layouts.app')

@section('title')
Connexion — Eventi
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('content')
    <div class="card">
        <div class="logo">Eventi</div>
        <p class="subtitle">Connectez-vous à votre compte</p>

        @if($errors->any())
            <div class="error-box">
                <span>&#9888;</span> {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
@csrf
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="vous@exemple.tn"
                    value="{{ old('email', '') }}"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn">Se connecter</button>
        </form>

        <div class="divider">ou</div>

        <div class="link-row">
            Pas encore de compte ? <a href="{{ route('register') }}">S'inscrire</a>
        </div>
    </div>
@endsection
