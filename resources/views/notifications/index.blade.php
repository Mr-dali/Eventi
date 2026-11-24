@extends('layouts.app')

@section('title', 'Notifications — Eventi')

@push('styles')
<style>
    .notifications-page { max-width: 820px; margin: 40px auto; padding: 0 20px; }
    .notifications-nav { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
    .notification-card { display: flex; justify-content: space-between; gap: 20px; align-items: flex-start; padding: 18px; margin: 12px 0; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
    .notification-card.unread { border-color: #818cf8; background: #eef2ff; }
    .notification-card h2 { margin: 0 0 8px; font-size: 17px; }
    .notification-card p { margin: 0 0 10px; }
    .notification-actions { display: flex; gap: 12px; align-items: center; flex-shrink: 0; }
    .notification-actions button { padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; }
    .notification-empty, .notification-success { padding: 14px; border-radius: 8px; background: #f3f4f6; }
</style>
@endpush

@section('content')
<main class="notifications-page">
    <nav class="notifications-nav">
        <a href="{{ route('events.index') }}">Événements</a>
        <a href="{{ route('profile.edit') }}">Mon profil</a>
        @if(auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PATCH')
                <button type="submit">Tout marquer comme lu</button>
            </form>
        @endif
    </nav>

    <h1>Mes notifications</h1>

    @if(session('success'))
        <p class="notification-success">{{ session('success') }}</p>
    @endif

    @forelse($notifications as $notification)
        <article class="notification-card {{ $notification->read_at ? '' : 'unread' }}">
            <div>
                <h2>{{ $notification->data['title'] ?? 'Notification Eventi' }}</h2>
                <p>{{ $notification->data['message'] ?? '' }}</p>
                <small>{{ $notification->created_at->format('d/m/Y H:i') }}</small>
            </div>
            <div class="notification-actions">
                @if(! $notification->read_at)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit">Marquer comme lue</button>
                    </form>
                @endif
                @if(! empty($notification->data['url']))
                    <a href="{{ $notification->data['url'] }}">Ouvrir</a>
                @endif
            </div>
        </article>
    @empty
        <p class="notification-empty">Aucune notification pour le moment.</p>
    @endforelse

    {{ $notifications->links() }}
</main>
@endsection
