@extends('layouts.app')

@section('title')
Utilisateurs — Admin Eventi
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}">
@endpush

@section('content')
@include('admin.partials.sidebar')

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <div>
            <h1 class="page-title">Utilisateurs</h1>
            <p class="page-sub">Gérez les comptes inscrits sur la plateforme</p>
        </div>
    </div>

    <!-- MINI STATS -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $totalUsers }}</div>
            <div class="stat-mini-lbl">Comptes au total</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $totalUsers - $totalAdmins }}</div>
            <div class="stat-mini-lbl">Utilisateurs</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $totalAdmins }}</div>
            <div class="stat-mini-lbl">Administrateurs</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $users->sum('nb_resa') }}</div>
            <div class="stat-mini-lbl">Réservations totales</div>
        </div>
    </div>

    <!-- ALERTES -->
    @if($errors->any())
        <div class="alert alert-error">⚠ {{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">✓ {{ session('success') }}</div>
    @endif

    <!-- FORMULAIRE MODIFICATION -->
    @if($editing)
    <div class="edit-panel">
        <div class="edit-title">✎ Modifier : {{ $editing['prenom'] . ' ' . $editing['nom'] }}</div>
        <form method="POST" action="{{ route('admin.users.update', $editing) }}">
@csrf
            @method('PUT')

            <div class="row-2">
                <div class="form-group">
                    <label>Nom <span>*</span></label>
                    <input type="text" name="nom" value="{{ $editing['nom'] }}" required>
                </div>
                <div class="form-group">
                    <label>Prénom <span>*</span></label>
                    <input type="text" name="prenom" value="{{ $editing['prenom'] }}" required>
                </div>
            </div>

            <div class="row-2">
                <div class="form-group">
                    <label>Adresse e-mail <span>*</span></label>
                    <input type="email" name="email" value="{{ $editing['email'] }}" required>
                </div>
                <div class="form-group">
                    <label>Rôle</label>
                    <select name="role">
                        <option value="user"  {{ $editing['role']==='user'  ? 'selected':'' }}>Utilisateur</option>
                        <option value="admin" {{ $editing['role']==='admin' ? 'selected':'' }}>Administrateur</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Nouveau mot de passe <small style="color:var(--muted);font-weight:400;">(laisser vide = inchangé)</small></label>
                <input type="password" name="new_password" placeholder="••••••••" autocomplete="new-password">
                <p class="hint">Au moins 8 caractères si renseigné</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Enregistrer</button>
                <a href="{{ route('admin.users.index') }}" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
    @endif

    <!-- RECHERCHE + TABLE -->
    <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Rechercher par nom, email…" oninput="filterUsers()">
    </div>

    <div class="panel">
        <div class="panel-head">
            <span class="panel-title">Tous les comptes</span>
            <span id="countBadge" style="font-size:13px;color:var(--muted);">{{ $totalUsers }} compte{{ $totalUsers > 1 ? 's' : '' }}</span>
        </div>
        <table id="usersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Réservations</th>
                    <th>Inscrit le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($users as $u)
                @php
$initiales = mb_strtoupper(mb_substr($u['prenom'],0,1) . mb_substr($u['nom'],0,1));
                    $isMe = $u['id'] === (int)auth()->id();
@endphp
                <tr class="user-row" data-search="{{ strtolower($u['nom'].' '.$u['prenom'].' '.$u['email']) }}">
                    <td style="color:var(--muted);font-size:12px;">#{{ $u['id'] }}</td>
                    <td>
                        <div class="user-cell">
                            <div class="avatar">{{ $initiales }}</div>
                            <div>
                                <div class="user-info-name">
                                    {{ $u['prenom'] . ' ' . $u['nom'] }}
                                    @if($isMe)<span class="me-tag">Vous</span>@endif
                                </div>
                                <div class="user-info-email">{{ $u['email'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $u['role'] === 'admin' ? 'badge-admin' : 'badge-user' }}">
                            {{ $u['role'] === 'admin' ? 'Admin' : 'Utilisateur' }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:500;">{{ $u['nb_resa'] }} réservation{{ $u['nb_resa'] > 1 ? 's' : '' }}</div>
                        <div style="font-size:11px;color:var(--muted);">{{ $u['places_total'] }} place{{ $u['places_total'] > 1 ? 's' : '' }} confirmée{{ $u['places_total'] > 1 ? 's' : '' }}</div>
                    </td>
                    <td style="color:var(--muted);white-space:nowrap;">{{ date('d/m/Y', strtotime($u['created_at'])) }}</td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('admin.users.edit', $u['id']) }}" class="btn-sm edit">✎ Modifier</a>
                            @if(!$isMe)
                                <form method="POST" action="{{ route('admin.users.destroy', $u['id']) }}" style="display:inline" onsubmit="return confirm('Supprimer le compte de {{ addslashes($u['prenom'].' '.$u['nom']) }} ? Ses réservations seront aussi supprimées.')">@csrf @method('DELETE')<button type="submit" class="btn-sm del" style="cursor:pointer;font-family:inherit">✕</button></form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection

@push('scripts')
@verbatim
<script>
function filterUsers() {
    const q     = document.getElementById('searchInput').value.toLowerCase().trim();
    const rows  = document.querySelectorAll('.user-row');
    let visible = 0;

    rows.forEach(row => {
        const match = row.dataset.search.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    document.getElementById('countBadge').textContent =
        visible + ' compte' + (visible > 1 ? 's' : '');
}
</script>
@endverbatim
@endpush
