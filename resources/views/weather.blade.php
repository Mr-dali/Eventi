@extends('layouts.app')

@section('title')
Weather App Pro
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/weather.css') }}">
@endpush

@section('content')
<button class="toggle" onclick="toggleDark()">🌙 / ☀️</button>

<div class="container">

<h1>🌤️ Weather App Pro</h1>

<form method="POST" action="{{ route('weather') }}">
@csrf
    <input type="text" name="city" placeholder="Entrer une ville (ex: Tunis)">
    <button type="submit">Chercher</button>
</form>

@if($error)
    <p style="color:#ffcccc">{{ $error }}</p>
@endif

@if($weather)

<div class="card">
    <h2>📍 {{ $weather["name"] }}</h2>
    <p>🌡 {{ $weather["main"]["temp"] }} °C</p>
    <p>☁️ {{ $weather["weather"][0]["description"] }}</p>
    <p>💧 Humidité: {{ $weather["main"]["humidity"] }}%</p>
    <p>🌬 Vent: {{ $weather["wind"]["speed"] }} m/s</p>
</div>

<h3>📅 Prévisions 5 jours</h3>

<div class="forecast">
@foreach($forecastDays as $d)
    <div class="day">
        <img src="https://openweathermap.org/img/wn/{{ $d["icon"] }}@2x.png">
        <div>{{ $d["temp"] }}°C</div>
        <small>{{ $d["desc"] }}</small>
    </div>
@endforeach
</div>

@endif

</div>
@endsection

@push('scripts')
@verbatim
<script>
function toggleDark() {
    document.body.classList.toggle("dark");

    if (document.body.classList.contains("dark")) {
        localStorage.setItem("theme", "dark");
    } else {
        localStorage.setItem("theme", "light");
    }
}

window.onload = function () {
    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark");
    }
};
</script>
@endverbatim
@endpush
