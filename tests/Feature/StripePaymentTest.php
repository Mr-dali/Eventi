<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_paid_reservation_creates_a_usd_checkout_session_and_holds_places(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent($user, '100.000', 3);
        $this->configureStripe();
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_123',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('events.reserve.store', $event), ['nb_places' => 2])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

        $reservation = Reservation::firstOrFail();
        $this->assertSame('en_attente', $reservation->statut);
        $this->assertSame('pending', $reservation->payment_status);
        $this->assertSame(6452, $reservation->stripe_amount_cents);
        $this->assertSame(2, Event::withPlaces()->findOrFail($event->id)->places_prises);
        $this->assertDatabaseCount('notifications', 1);

        Http::assertSent(fn (ClientRequest $request): bool =>
            $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
            && $request->data()['line_items'][0]['price_data']['currency'] === 'usd'
            && $request->data()['line_items'][0]['price_data']['unit_amount'] === 3226
            && $request->data()['line_items'][0]['quantity'] === 2
        );
    }

    public function test_signed_paid_webhook_confirms_reservation_idempotently(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent($user);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'nb_places' => 1,
            'statut' => 'en_attente',
            'payment_status' => 'pending',
            'stripe_checkout_session_id' => 'cs_test_123',
            'stripe_checkout_url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
            'stripe_amount_cents' => 3226,
            'stripe_tnd_per_usd' => 3.1,
            'expires_at' => now()->addMinutes(30),
        ]);
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $payload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'payment_status' => 'paid',
                    'amount_total' => 3226,
                    'currency' => 'usd',
                    'payment_intent' => 'pi_test_123',
                    'metadata' => [
                        'reservation_id' => (string) $reservation->id,
                        'user_id' => (string) $user->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->postStripeWebhook($payload)->assertNoContent();
        $this->postStripeWebhook($payload)->assertNoContent();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'statut' => 'confirmee',
            'payment_status' => 'paid',
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_invalid_stripe_signature_cannot_confirm_a_reservation(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent($user);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'nb_places' => 1,
            'statut' => 'en_attente',
            'payment_status' => 'pending',
            'stripe_checkout_session_id' => 'cs_test_123',
            'stripe_amount_cents' => 3226,
            'expires_at' => now()->addMinutes(30),
        ]);
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_123']],
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', route('stripe.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.now()->timestamp.',v1=invalid',
        ], $payload)->assertBadRequest();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'statut' => 'en_attente',
            'payment_status' => 'pending',
        ]);
    }

    public function test_paid_checkout_is_blocked_until_stripe_and_exchange_rate_are_configured(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent($user);
        config([
            'services.stripe.secret' => 'sk_test_123',
            'services.stripe.webhook_secret' => 'whsec_test',
            'services.stripe.tnd_per_usd' => null,
        ]);

        $this->actingAs($user)
            ->from(route('events.reserve', $event))
            ->post(route('events.reserve.store', $event), ['nb_places' => 1])
            ->assertRedirect(route('events.reserve', $event))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_free_reservation_does_not_require_stripe_configuration(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent($user, '0.000');

        $this->actingAs($user)
            ->post(route('events.reserve.store', $event), ['nb_places' => 1])
            ->assertRedirect(route('events.reserve', $event));

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'statut' => 'confirmee',
            'payment_status' => 'not_required',
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_paid_reservation_page_shows_the_usd_estimate_and_exchange_rate(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent($user);
        $this->configureStripe();

        $this->actingAs($user)
            ->get(route('events.reserve', $event))
            ->assertOk()
            ->assertSee('32.26 USD')
            ->assertSee('1 USD = 3.1000 TND')
            ->assertSee('Payer en USD avec Stripe');
    }

    private function createUser(): User
    {
        return User::create([
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => uniqid('user-').'@example.test',
            'password' => 'password',
        ]);
    }

    private function createEvent(User $creator, string $price = '100.000', int $capacity = 10): Event
    {
        return Event::create([
            'titre' => 'Événement de test',
            'description' => 'Description',
            'date_evenement' => now()->addWeek(),
            'lieu' => 'Tunis',
            'capacite' => $capacity,
            'prix' => $price,
            'categorie' => 'autre',
            'statut' => 'actif',
            'created_by' => $creator->id,
        ]);
    }

    private function configureStripe(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_123',
            'services.stripe.webhook_secret' => 'whsec_test',
            'services.stripe.tnd_per_usd' => 3.1,
        ]);
    }

    private function postStripeWebhook(string $payload)
    {
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        return $this->call('POST', route('stripe.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $payload);
    }
}
