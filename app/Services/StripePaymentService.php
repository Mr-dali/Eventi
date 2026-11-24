<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Reservation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripePaymentService
{
    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'))
            && filled(config('services.stripe.webhook_secret'))
            && $this->tndPerUsd() !== null;
    }

    public function tndPerUsd(): ?float
    {
        $rate = config('services.stripe.tnd_per_usd');

        if (! is_numeric($rate) || (float) $rate <= 0) {
            return null;
        }

        return (float) $rate;
    }

    public function unitAmountInUsdCents(string|int|float $priceInTnd): ?int
    {
        $rate = $this->tndPerUsd();

        if ($rate === null) {
            return null;
        }

        return (int) round(((float) $priceInTnd / $rate) * 100, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(Reservation $reservation, Event $event): array
    {
        $unitAmount = $this->unitAmountInUsdCents($event->prix);

        if ($unitAmount === null || $unitAmount < 1 || ! $reservation->expires_at) {
            throw new StripeCheckoutException('Le paiement Stripe n’est pas correctement configuré.');
        }

        $metadata = [
            'reservation_id' => (string) $reservation->id,
            'user_id' => (string) $reservation->user_id,
        ];

        $response = Http::asForm()
            ->withToken(config('services.stripe.secret'))
            ->withHeaders([
                'Idempotency-Key' => "eventi-reservation-{$reservation->id}-{$reservation->expires_at->timestamp}",
            ])
            ->timeout(20)
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => route('payments.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payments.cancel').'?session_id={CHECKOUT_SESSION_ID}',
                'expires_at' => $reservation->expires_at->timestamp,
                'customer_email' => $reservation->user->email,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'quantity' => $reservation->nb_places,
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $unitAmount,
                        'product_data' => [
                            'name' => $event->titre,
                        ],
                    ],
                ]],
                'metadata' => $metadata,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
            ]);

        if (! $response->successful()) {
            Log::error('Stripe Checkout session creation failed.', [
                'reservation_id' => $reservation->id,
                'http_status' => $response->status(),
                'stripe_message' => $response->json('error.message'),
            ]);

            throw new StripeCheckoutException('Stripe n’a pas pu démarrer le paiement. Réessayez plus tard.');
        }

        $session = $response->json();
        $checkoutUrl = is_array($session) ? ($session['url'] ?? null) : null;
        $checkoutHost = is_string($checkoutUrl) ? parse_url($checkoutUrl, PHP_URL_HOST) : null;

        if (
            ! is_array($session)
            || ! is_string($session['id'] ?? null)
            || ! is_string($checkoutUrl)
            || $checkoutHost !== 'checkout.stripe.com'
        ) {
            Log::error('Stripe returned an invalid Checkout session response.', [
                'reservation_id' => $reservation->id,
                'http_status' => $response->status(),
            ]);

            throw new StripeCheckoutException('Stripe a retourné une réponse de paiement invalide.');
        }

        return [
            'id' => $session['id'],
            'url' => $checkoutUrl,
        ];
    }

    public function hasValidWebhookSignature(string $payload, ?string $signatureHeader): bool
    {
        $secret = config('services.stripe.webhook_secret');

        if (! is_string($secret) || $secret === '' || ! is_string($signatureHeader)) {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if ($key === 't' && is_string($value) && ctype_digit($value)) {
                $timestamp = (int) $value;
            } elseif ($key === 'v1' && is_string($value)) {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === [] || abs(now()->timestamp - $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
