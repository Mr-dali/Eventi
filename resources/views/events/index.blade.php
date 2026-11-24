@extends('layouts.app')

@section('title')
Événements — Eventi
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/events.css') }}">
@endpush

@section('content')
<nav>
    <div class="nav-logo">Eventi</div>
    <div class="nav-right">
        <div class="nav-user">
            Bonjour, <span>{{ auth()->user()->prenom }}</span>
            <a href="{{ route('notifications.index') }}" style="color:var(--accent-light);text-decoration:none;font-size:13px;">
                Notifications ({{ auth()->user()->unreadNotifications()->count() }})
            </a>
            <a href="{{ route('profile.edit') }}" style="color:var(--accent-light);text-decoration:none;font-size:13px;">Mon profil</a>
            @if(auth()->user()->role === 'admin')
                &nbsp;·&nbsp; <a href="{{ route('admin.dashboard') }}" style="color:var(--accent-light);text-decoration:none;font-size:13px;">Admin ↗</a>
            @endif
            <button onclick="openWeather()" style="
    background: transparent;
    border: 1px solid var(--border);
    color: var(--accent-light);
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
">
🌤 Weather
</button>
        </div>
        <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();" class="btn-logout">Déconnexion</a>
    </div>
</nav>

<div class="hero">
    <h1>Découvrez les<br><em>prochains événements</em></h1>
    <p>Réservez votre place en quelques secondes pour les événements à venir.</p>
</div>

<div class="container">

    
    <div class="search-wrapper">
        <svg class="ico-search" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.8"/>
            <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        <input
            type="text"
            id="searchInput"
            placeholder="Rechercher par titre, lieu, date…"
            autocomplete="off"
            spellcheck="false"
        >
        <button id="clearBtn" title="Effacer">✕</button>
    </div>

    
    <div class="filters">
        <button class="filter-btn active" data-filter="all">🎯 Tous</button>
        <button class="filter-btn" data-filter="sport">🏆 Sport</button>
        <button class="filter-btn" data-filter="culture">🎭 Culture</button>
        <button class="filter-btn" data-filter="musique">🎵 Musique</button>
        <button class="filter-btn" data-filter="disponible">✅ Disponibles</button>
    </div>

    <div class="section-header">
        <span class="section-title">Événements disponibles</span>
        <span class="count-badge" id="countBadge">
            {{ $totalEvents }} événement{{ $totalEvents > 1 ? 's' : '' }}
        </span>
    </div>

    @if(empty($events))
        <div class="empty">
            <div class="empty-icon">📅</div>
            <h3>Aucun événement disponible</h3>
            <p>Revenez bientôt pour découvrir les prochains événements.</p>
        </div>
    @else

        <div class="events-grid" id="eventsGrid">
            @foreach($events as $e)
                @php
                    $restant = (int)$e['places_restantes'];
                    $plein   = $restant <= 0;
                    $peu     = !$plein && $restant <= 10;
                    $dejaRe  = in_array($e['id'], $reserved);
                    $dateF   = $e->date_evenement->translatedFormat('d M Y · H\\hi');
                    $cat     = $e->categorie;
                    $emoji   = ['sport'=>'⚽','culture'=>'🎭','musique'=>'🎵','autre'=>'🎪'][$cat] ?? '🎪';
                    $searchData = mb_strtolower($e['titre'].' '.$e['description'].' '.$e['lieu'].' '.$dateF);
                @endphp
                <div class="event-card"
                     data-cat="{{ $cat }}"
                     data-full="{{ $plein ? '1' : '0' }}"
                     data-search="{{ $searchData }}">

                    <div class="event-img">
                        @if(!empty($e['image']))
                            <img src="{{ asset('assets/img/'.$e['image']) }}" alt="">
                        @elseif(!empty($emoji))
                            {{ $emoji }}
                        @else
                            🎪
                        @endif

                        @if($cat === 'sport')
                            <span class="sport-tag">⚡ Sport</span>
                        @endif

                        @if($plein)
                            <span class="event-status status-full">Complet</span>
                        @elseif($peu)
                            <span class="event-status status-few">{{ $restant }} places</span>
                        @else
                            <span class="event-status status-ok">Disponible</span>
                        @endif
                    </div>

                    <div class="event-body">
                        <div class="event-date">{{ $dateF }}</div>
                        <div class="event-title">{{ $e['titre'] }}</div>
                        <div class="event-desc">{{ $e['description'] }}</div>
                        <div class="event-meta">
                            <span>📍 {{ $e['lieu'] }}</span>
                            @if(!$plein)
                                <span>👥 {{ $restant }} places restantes</span>
                            @else
                                <span style="color:var(--danger)">🚫 Complet</span>
                            @endif
                        </div>
                        <div class="event-footer">
                            <div class="event-price">
                                {{ number_format($e['prix'], 3, '.', '') }} <small>TND</small>
                            </div>
                            @if($dejaRe)
                                <span class="btn-reserved">✓ Réservé</span>
                            @elseif($plein)
                                <span class="btn-full">Complet</span>
                            @else
                                <a href="{{ route('events.reserve', $e['id']) }}" class="btn-reserve">Réserver</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="no-results" id="noResults">
            <div class="no-results-icon">🔎</div>
            <h3>Aucun résultat pour « <span class="query-term" id="queryTerm"></span> »</h3>
            <p>Essayez un autre mot-clé : titre, lieu ou date.</p>
        </div>

    @endif
</div>


<!-- WEATHER MODAL -->
<div id="weatherModal" style="
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.75);
    z-index:99999;
    justify-content:center;
    align-items:center;
">

    <div style="
        width:85%;
        height:90%;
        background:white;
        border-radius:12px;
        overflow:hidden;
        position:relative;
    ">

        <button onclick="closeWeather()" style="
            position:absolute;
            top:10px;
            right:10px;
            z-index:999999;
            background:#ff5c6a;
            color:white;
            border:none;
            padding:6px 10px;
            border-radius:6px;
            cursor:pointer;
        ">✕</button>

        <iframe src="{{ route('weather') }}" style="width:100%;height:100%;border:none;"></iframe>

    </div>
</div>
@endsection

@push('scripts')
@verbatim
<script>
(function () {
    var searchInput = document.getElementById('searchInput');
    var clearBtn    = document.getElementById('clearBtn');
    var countBadge  = document.getElementById('countBadge');
    var noResults   = document.getElementById('noResults');
    var queryTerm   = document.getElementById('queryTerm');
    var filterBtns  = document.querySelectorAll('.filter-btn');
    var cards       = document.querySelectorAll('.event-card');

    var currentFilter = 'all';
    var currentQuery  = '';

    function normalize(str) {
        return str.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function escapeRE(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlight(text, term) {
        if (!term) return text;
        var re = new RegExp('(' + escapeRE(term) + ')', 'gi');
        return text.replace(re, '<mark class="hl">$1</mark>');
    }

    
    cards.forEach(function (card) {
        card.dataset.titleRaw = card.querySelector('.event-title').textContent.trim();
        card.dataset.descRaw  = card.querySelector('.event-desc').textContent.trim();
    });

    function applyFilters() {
        var query   = normalize(currentQuery.trim());
        var visible = 0;

        cards.forEach(function (card) {
            var cat    = card.dataset.cat;
            var isFull = card.dataset.full === '1';
            var hay    = normalize(card.dataset.search || '');

            
            var catOk = false;
            if      (currentFilter === 'all')       catOk = true;
            else if (currentFilter === 'sport')      catOk = cat === 'sport';
            else if (currentFilter === 'culture')    catOk = cat === 'culture';
            else if (currentFilter === 'musique')    catOk = cat === 'musique';
            else if (currentFilter === 'disponible') catOk = !isFull;

          
            var textOk = !query || hay.includes(query);

            var show = catOk && textOk;
            card.classList.toggle('hidden', !show);

            if (show) {
                visible++;
                card.querySelector('.event-title').innerHTML = highlight(card.dataset.titleRaw, currentQuery.trim());
                card.querySelector('.event-desc').innerHTML  = highlight(card.dataset.descRaw,  currentQuery.trim());
            } else {
                card.querySelector('.event-title').textContent = card.dataset.titleRaw;
                card.querySelector('.event-desc').textContent  = card.dataset.descRaw;
            }
        });

       
        countBadge.textContent = visible + ' événement' + (visible > 1 ? 's' : '');

       
        if (noResults) {
            noResults.style.display = visible === 0 ? 'block' : 'none';
            if (queryTerm) queryTerm.textContent = currentQuery.trim();
        }

        
        clearBtn.classList.toggle('visible', currentQuery.length > 0);
    }

  
    searchInput.addEventListener('input', function () {
        currentQuery = this.value;
        applyFilters();
    });

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        currentQuery = '';
        applyFilters();
        searchInput.focus();
    });

    
    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            applyFilters();
        });
    });

    
    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && document.activeElement !== searchInput) {
            e.preventDefault();
            searchInput.focus();
        }
    });
})();
function openWeather() {
    document.getElementById("weatherModal").style.display = "flex";
}

function closeWeather() {
    document.getElementById("weatherModal").style.display = "none";
}

// click outside
window.addEventListener("click", function(e) {
    let modal = document.getElementById("weatherModal");
    if (e.target === modal) {
        modal.style.display = "none";
    }
});
</script>
@endverbatim
@endpush
