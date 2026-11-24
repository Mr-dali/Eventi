<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->text('stripe_checkout_url')->nullable();
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->unsignedInteger('stripe_amount_cents')->nullable();
            $table->decimal('stripe_tnd_per_usd', 12, 6)->nullable();
            $table->string('payment_status', 20)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_stripe_checkout_session_id_unique');
            $table->dropUnique('reservations_stripe_payment_intent_id_unique');
            $table->dropIndex('reservations_expires_at_index');
            $table->dropColumn([
                'stripe_checkout_session_id',
                'stripe_checkout_url',
                'stripe_payment_intent_id',
                'stripe_amount_cents',
                'stripe_tnd_per_usd',
                'payment_status',
                'expires_at',
                'paid_at',
            ]);
        });
    }
};
