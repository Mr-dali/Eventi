<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class StripePaymentController extends Controller
{
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        abort_unless(is_string($sessionId) && str_starts_with($sessionId, 'cs_'), 404);

        $reservation = Reservation::with('event')
            ->where('user_id', auth()->id())
            ->where('stripe_checkout_session_id', $sessionId)
            ->firstOrFail();

        return view('payments.success', compact('reservation'));
    }

    public function cancel(Request $request)
    {
        $sessionId = $request->query('session_id');

        abort_unless(is_string($sessionId) && str_starts_with($sessionId, 'cs_'), 404);

        $reservation = Reservation::with('event')
            ->where('user_id', auth()->id())
            ->where('stripe_checkout_session_id', $sessionId)
            ->firstOrFail();

        return redirect()
            ->route('events.reserve', $reservation->event)
            ->with('payment_cancelled', true);
    }
}
