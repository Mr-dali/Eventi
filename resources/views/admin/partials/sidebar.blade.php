<aside class="sidebar">
    <div class="sidebar-logo">
        Eventi
        <small>Administration</small>
    </div>

    <span class="nav-section">Menu</span>
    <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <span class="icon">▣</span> Dashboard
    </a>
    <a href="{{ route('admin.events.create') }}" class="nav-item {{ request()->routeIs('admin.events.create') ? 'active' : '' }}">
        <span class="icon">＋</span> Ajouter événement
    </a>
    <a href="{{ route('admin.events.index') }}" class="nav-item {{ request()->routeIs('admin.events.index', 'admin.events.edit') ? 'active' : '' }}">
        <span class="icon">✎</span> Gérer événements
    </a>
    <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <span class="icon">◎</span> Utilisateurs
    </a>
    <a href="{{ route('notifications.index') }}" class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
        <span class="icon">◉</span> Notifications ({{ auth()->user()->unreadNotifications()->count() }})
    </a>
    <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
        <span class="icon">◎</span> Mon profil
    </a>

    <span class="nav-section">Site</span>
    <a href="{{ route('events.index') }}" class="nav-item">
        <span class="icon">↗</span> Voir le site
    </a>

    <div class="sidebar-bottom">
        <div class="admin-info">
            <strong>{{ auth()->user()->prenom . ' ' . auth()->user()->nom }}</strong>
            Administrateur
        </div>
        <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();" class="btn-logout">Déconnexion</a>
    </div>
</aside>
