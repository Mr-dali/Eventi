<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\EventiNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_and_change_password_with_current_password(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'nom' => 'Nouveau',
                'prenom' => 'Prénom',
                'email' => 'updated@example.test',
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Nouveau', $user->nom);
        $this->assertSame('updated@example.test', $user->email);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_password_change_requires_the_correct_current_password(): void
    {
        $user = $this->createUser();
        $originalHash = $user->password;

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_user_can_view_and_mark_their_notification_as_read(): void
    {
        $user = $this->createUser();
        $user->notify(new EventiNotification(
            'reservation-created:1',
            'Réservation confirmée',
            'Votre réservation est confirmée.',
            route('events.index'),
        ));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Réservation confirmée');

        $this->actingAs($user)
            ->patch(route('notifications.read', $notification->id))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_event_cancellation_notifies_confirmed_reservation_holders(): void
    {
        Notification::fake();
        $admin = $this->createUser('admin@example.test', 'admin');
        $customer = $this->createUser('customer@example.test');
        $event = $this->createEvent($admin);
        Reservation::create([
            'user_id' => $customer->id,
            'event_id' => $event->id,
            'nb_places' => 1,
            'statut' => 'confirmee',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.events.update', $event), [
                'titre' => $event->titre,
                'description' => $event->description,
                'date_evenement' => $event->date_evenement->format('Y-m-d H:i:s'),
                'lieu' => $event->lieu,
                'capacite' => $event->capacite,
                'prix' => $event->prix,
                'statut' => 'annule',
                'categorie' => $event->categorie,
            ])
            ->assertRedirect(route('admin.events.index'));

        Notification::assertSentTo(
            $customer,
            EventiNotification::class,
            fn (EventiNotification $notification): bool => $notification->title === 'Événement annulé'
                && in_array('mail', $notification->via($customer), true)
                && in_array('database', $notification->via($customer), true),
        );
    }

    public function test_confirmed_reservation_receives_reminders_at_three_two_and_one_hours(): void
    {
        $now = Carbon::parse('2026-11-24 10:00:00', 'Africa/Tunis');
        Carbon::setTestNow($now);

        try {
            $user = $this->createUser();
            $event = $this->createEvent($user, $now->copy()->addHours(3));
            $reservation = Reservation::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'nb_places' => 1,
                'statut' => 'confirmee',
            ]);

            $this->artisan('events:send-reminders')->assertExitCode(0);
            Carbon::setTestNow($now->copy()->addHour());
            $this->artisan('events:send-reminders')->assertExitCode(0);
            Carbon::setTestNow($now->copy()->addHours(2));
            $this->artisan('events:send-reminders')->assertExitCode(0);
            $this->artisan('events:send-reminders')->assertExitCode(0);

            $this->assertCount(3, $user->notifications()->get());
            $keys = $user->notifications()->get()->map(fn ($notification) => $notification->data['key'])->all();
            $this->assertEqualsCanonicalizing([
                "event-reminder:{$reservation->id}:3",
                "event-reminder:{$reservation->id}:2",
                "event-reminder:{$reservation->id}:1",
            ], $keys);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createUser(string $email = 'user@example.test', string $role = 'user'): User
    {
        $user = User::create([
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => $email,
            'password' => 'old-password',
        ]);
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function createEvent(User $creator, ?Carbon $date = null): Event
    {
        return Event::create([
            'titre' => 'Événement de test',
            'description' => 'Description',
            'date_evenement' => $date ?? now()->addWeek(),
            'lieu' => 'Tunis',
            'capacite' => 20,
            'prix' => '0.000',
            'categorie' => 'autre',
            'statut' => 'actif',
            'created_by' => $creator->id,
        ]);
    }
}
