@extends('layouts.app')

@section('title')
Ajouter un événement — Admin
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-add-event.css') }}">
@endpush

@section('content')
@include('admin.partials.sidebar')

<main class="main">
    <div class="page-header">
        <a href="{{ route('admin.dashboard') }}">← Dashboard</a>
        <span style="color:var(--border)">/</span>
        <h1 class="page-title">Ajouter un événement</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-error">⚠ {{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">✓ {{ session('success') }} <a href="{{ route('admin.events.index') }}" style="color:var(--success);font-weight:500;">Voir les événements →</a></div>
    @endif

    <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data" id="mainForm">
@csrf
        <div class="form-grid">

            <!-- COLONNE GAUCHE -->
            <div>
                <div class="panel">
                    <div class="panel-title">Informations générales</div>

                    <div class="form-group">
                        <label>Titre <span>*</span></label>
                        <input type="text" name="titre" placeholder="Ex : Conférence Tech Tunis 2025" value="{{ old('titre', '') }}" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Décrivez l'événement...">{{ old('description', '') }}</textarea>
                    </div>

                    <div class="row-2">
                        <div class="form-group">
                            <label>Date & heure <span>*</span></label>
                            <input type="datetime-local" name="date_evenement" value="{{ old('date_evenement', '') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="statut">
                                <option value="actif"   {{ (old('statut', '')) === 'actif'   ? 'selected' : '' }}>Actif</option>
                                <option value="annule"  {{ (old('statut', '')) === 'annule'  ? 'selected' : '' }}>Annulé</option>
                                <option value="termine" {{ (old('statut', '')) === 'termine' ? 'selected' : '' }}>Terminé</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lieu <span>*</span></label>
                        <input type="text" name="lieu" placeholder="Ex : Palais des Congrès, Tunis" value="{{ old('lieu', '') }}" required>
                    </div>

                    <div class="form-group">
                <label>Catégorie</label>
                <select name="categorie">
                    @foreach(['autre'=>'Autre','sport'=>'Sport','culture'=>'Culture','musique'=>'Musique'] as $k => $lbl)
                        <option value="{{ $k }}" {{ old('categorie', 'autre') === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

                    <div class="row-2">
                        <div class="form-group">
                            <label>Capacité <span>*</span></label>
                            <input type="number" name="capacite" min="1" placeholder="200" value="{{ old('capacite', '') }}" required oninput="updateRecap()">
                        </div>
                        <div class="form-group">
                            <label>Prix (TND)</label>
                            <input type="number" name="prix" min="0" step="0.001" placeholder="0.000" value="{{ old('prix', '0') }}" oninput="updateRecap()">
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div class="panel" style="margin-top:20px;">
                    <div class="panel-title">Image de l'événement</div>
                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="image" id="imageInput" accept="image/jpg,image/jpeg,image/png,image/webp" onchange="previewImage(this)">
                        <div class="upload-icon">🖼</div>
                        <div class="upload-text">Glissez une image ou <strong>cliquez pour choisir</strong></div>
                        <p class="hint" style="margin-top:8px;">JPG, PNG, WEBP · max 2 Mo</p>
                    </div>
                    <img id="preview" src="" alt="Aperçu">
                </div>
            </div>

            <!-- COLONNE DROITE -->
            <div>
                <div class="panel">
                    <div class="panel-title">Récapitulatif</div>
                    <div class="recap-item"><span>Titre</span>          <strong id="r-titre">—</strong></div>
                    <div class="recap-item"><span>Date</span>           <strong id="r-date">—</strong></div>
                    <div class="recap-item"><span>Lieu</span>           <strong id="r-lieu">—</strong></div>
                    <div class="recap-item"><span>Capacité</span>       <strong id="r-cap">—</strong></div>
                    <div class="recap-item"><span>Prix / place</span>   <strong id="r-prix">—</strong></div>
                    <div class="recap-item"><span>Revenu potentiel</span><strong id="r-rev">—</strong></div>

                    <button type="submit" class="btn-submit">Créer l'événement</button>
                </div>
            </div>

        </div>
    </form>
</main>
@endsection

@push('scripts')
@verbatim
<script>
function previewImage(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('preview');
        img.src = e.target.result;
        img.style.display = 'block';
        document.querySelector('.upload-zone .upload-icon').style.display = 'none';
        document.querySelector('.upload-zone .upload-text').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function updateRecap() {
    const titre = document.querySelector('[name=titre]').value || '—';
    const date  = document.querySelector('[name=date_evenement]').value;
    const lieu  = document.querySelector('[name=lieu]').value || '—';
    const cap   = parseInt(document.querySelector('[name=capacite]').value) || 0;
    const prix  = parseFloat(document.querySelector('[name=prix]').value) || 0;

    document.getElementById('r-titre').textContent = titre.length > 22 ? titre.slice(0,22)+'…' : titre;
    document.getElementById('r-date').textContent  = date ? new Date(date).toLocaleDateString('fr-FR', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '—';
    document.getElementById('r-lieu').textContent  = lieu.length > 22 ? lieu.slice(0,22)+'…' : lieu;
    document.getElementById('r-cap').textContent   = cap > 0 ? cap + ' places' : '—';
    document.getElementById('r-prix').textContent  = prix > 0 ? prix.toFixed(3) + ' TND' : 'Gratuit';
    document.getElementById('r-rev').textContent   = cap > 0 && prix > 0 ? (cap * prix).toFixed(3) + ' TND' : '—';
}

['titre','date_evenement','lieu','capacite','prix'].forEach(name => {
    document.querySelector(`[name=${name}]`).addEventListener('input', updateRecap);
});
</script>
@endverbatim
@endpush
