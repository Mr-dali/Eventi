@extends('layouts.app')

@section('title')
Gérer les événements — Admin
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-events.css') }}">
@endpush

@section('content')
@include('admin.partials.sidebar')

<main class="main">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
        <h1 class="page-title">Gérer les événements</h1>
        <a href="{{ route('admin.events.create') }}" class="btn-add">+ Nouvel événement</a>
    </div>
    <p class="page-sub">{{ count($events) }} événement{{ count($events) > 1 ? 's' : '' }} au total</p>

    @if($errors->any())
        <div class="alert alert-error">⚠ {{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">✓ {{ session('success') }}</div>
    @endif

    <!-- FORMULAIRE MODIFICATION -->
    @if($editing)
    <div class="edit-panel">
        <div class="edit-title">✎ Modifier : {{ $editing['titre'] }}</div>
        <form method="POST" action="{{ route('admin.events.update', $editing) }}" enctype="multipart/form-data">
@csrf
            @method('PUT')

            <div class="form-group">
                <label>Titre <span>*</span></label>
                <input type="text" name="titre" value="{{ $editing['titre'] }}" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description">{{ $editing['description'] }}</textarea>
            </div>

            <div class="row-3">
                <div class="form-group">
                    <label>Date & heure <span>*</span></label>
                    <input type="datetime-local" name="date_evenement" value="{{ date('Y-m-d\TH:i', strtotime($editing['date_evenement'])) }}" required>
                </div>
                <div class="form-group">
                    <label>Capacité <span>*</span></label>
                    <input type="number" name="capacite" min="1" value="{{ $editing['capacite'] }}" required>
                </div>
                <div class="form-group">
                    <label>Prix (TND)</label>
                    <input type="number" name="prix" min="0" step="0.001" value="{{ $editing['prix'] }}">
                </div>
            </div>

            <div class="row-2">
                <div class="form-group">
                    <label>Lieu <span>*</span></label>
                    <input type="text" name="lieu" value="{{ $editing['lieu'] }}" required>
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut">
                        <option value="actif"   {{ $editing['statut']==='actif'   ? 'selected':'' }}>Actif</option>
                        <option value="annule"  {{ $editing['statut']==='annule'  ? 'selected':'' }}>Annulé</option>
                        <option value="termine" {{ $editing['statut']==='termine' ? 'selected':'' }}>Terminé</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Catégorie</label>
                <select name="categorie">
                    @foreach(['autre'=>'Autre','sport'=>'Sport','culture'=>'Culture','musique'=>'Musique'] as $k => $lbl)
                        <option value="{{ $k }}" {{ $editing['categorie'] === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Nouvelle image (optionnel)</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Enregistrer les modifications</button>
                <a href="{{ route('admin.events.index') }}" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
    @endif

    <!-- LISTE DES ÉVÉNEMENTS -->
    <div class="panel">
        <div class="panel-head">
            <span class="panel-title">Tous les événements</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Titre</th>
                    <th>Date</th>
                    <th>Lieu</th>
                    <th>Remplissage</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($events as $e)
                @php
$pct = $e['capacite'] > 0 ? min(100, round($e['places_prises'] / $e['capacite'] * 100)) : 0;
                    $color = $pct >= 90 ? 'var(--danger)' : ($pct >= 60 ? 'var(--warn)' : 'var(--accent)');
                    $badgeClass = match($e['statut']) {
                        'actif'   => 'badge-actif',
                        'annule'  => 'badge-annule',
                        'termine' => 'badge-termine',
                        default   => ''
                    };
@endphp
                <tr>
                    <td style="color:var(--muted);font-size:12px;">#{{ $e['id'] }}</td>
                    <td>
                        <div style="font-weight:500;">{{ mb_strimwidth($e['titre'], 0, 30, '…') }}</div>
                        <div style="font-size:11px;color:var(--muted);">{{ $e['nb_resa'] }} réservation{{ $e['nb_resa'] > 1 ? 's' : '' }}</div>
                    </td>
                    <td style="color:var(--muted);white-space:nowrap;">{{ date('d/m/Y H:i', strtotime($e['date_evenement'])) }}</td>
                    <td style="color:var(--muted);">{{ mb_strimwidth($e['lieu'], 0, 22, '…') }}</td>
                    <td style="min-width:100px;">
                        <div style="font-size:12px;color:var(--muted);">{{ $e['places_prises'] }}/{{ $e['capacite'] }} ({{ $pct }}%)</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:{{ $pct }}%;background:{{ $color }}"></div>
                        </div>
                    </td>
                    <td style="white-space:nowrap;">{{ number_format($e['prix'], 3, '.', '') }} TND</td>
                    <td><span class="badge {{ $badgeClass }}">{{ ucfirst($e['statut']) }}</span></td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('admin.events.edit', $e['id']) }}" class="btn-sm edit">✎ Modifier</a>
                            <form method="POST" action="{{ route('admin.events.destroy', $e['id']) }}" style="display:inline" onsubmit="return confirm('Supprimer cet événement ? Toutes ses réservations seront aussi supprimées.')">@csrf @method('DELETE')<button type="submit" class="btn-sm del" style="cursor:pointer;font-family:inherit">✕ Supprimer</button></form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection
