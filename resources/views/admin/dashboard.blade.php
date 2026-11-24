@extends('layouts.app')

@section('title')
Dashboard — Admin Eventi
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
@endpush

@section('content')
@include('admin.partials.sidebar')

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h1 class="page-title">Tableau de bord</h1>
                <p class="page-sub">Vue d'ensemble de la plateforme</p>
            </div>
            <a href="{{ route('admin.events.create') }}" class="btn-add">+ Nouvel événement</a>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card purple">
            <div class="stat-icon">🎪</div>
            <div class="stat-val">{{ $stats['total_events'] }}</div>
            <div class="stat-label">Événements</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">👤</div>
            <div class="stat-val">{{ $stats['total_users'] }}</div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon">🎫</div>
            <div class="stat-val">{{ $stats['total_resa'] }}</div>
            <div class="stat-label">Réservations</div>
        </div>
        <div class="stat-card pink">
            <div class="stat-icon">💰</div>
            <div class="stat-val">{{ number_format($stats['revenus'], 0, '.', ' ') }}</div>
            <div class="stat-label">Revenus (USD)</div>
        </div>
    </div>

    <!-- TABLES -->
    <div class="grid-2">

        <!-- Derniers événements -->
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Derniers événements</span>
                <a href="{{ route('admin.events.index') }}" class="panel-link">Voir tout →</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Capacité</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($derniers_events as $ev)
                    @php
$pct = $ev['capacite'] > 0
                            ? min(100, round($ev['places_prises'] / $ev['capacite'] * 100))
                            : 0;
@endphp
                    <tr>
                        <td>
                            <div style="font-weight:500;font-size:13px;">{{ mb_strimwidth($ev['titre'], 0, 28, '…') }}</div>
                            <div style="font-size:11px;color:var(--muted);">{{ date('d/m/Y', strtotime($ev['date_evenement'])) }}</div>
                        </td>
                        <td>
                            <div style="font-size:12px;color:var(--muted);">{{ $ev['places_prises'] }}/{{ $ev['capacite'] }}</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:{{ $pct }}%;background:{{ $pct >= 90 ? 'var(--danger)' : ($pct >= 60 ? 'var(--warn)' : 'var(--accent)') }}"></div>
                            </div>
                        </td>
                        <td><span class="badge badge-actif">{{ $ev['statut'] }}</span></td>
                        <td>
                            <div class="action-btns">
                                <a href="{{ route('admin.events.edit', $ev['id']) }}" class="btn-sm">✎</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <!-- Dernières réservations -->
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Dernières réservations</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Événement</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($dernieres_resa as $r)
                    <tr>
                        <td>
                            <div style="font-weight:500;">{{ $r['user_name'] }}</div>
                            <div style="font-size:11px;color:var(--muted);">{{ date('d/m · H:i', strtotime($r['created_at'])) }}</div>
                        </td>
                        <td style="color:var(--muted);">{{ mb_strimwidth($r['event_titre'], 0, 24, '…') }}</td>
                        <td>
                            @php
$cls = match($r['statut']) {
                                    'confirmee'  => 'badge-ok',
                                    'en_attente' => 'badge-wait',
                                    'annulee'    => 'badge-cancel',
                                    default      => ''
                                };
                                $lbl = $r['payment_status'] === 'review'
                                    ? 'Paiement à vérifier'
                                    : match($r['statut']) {
                                    'confirmee'  => 'Confirmée',
                                    'en_attente' => 'En attente',
                                    'annulee'    => 'Annulée',
                                    default      => $r['statut']
                                };
@endphp
                            <span class="badge {{ $cls }}">{{ $lbl }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
</main>
@endsection
