<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 200);
            $table->text('description')->nullable();
            $table->dateTime('date_evenement');
            $table->string('lieu', 200);
            $table->unsignedInteger('capacite')->default(0);
            $table->decimal('prix', 10, 3)->default(0);
            $table->string('image')->nullable();
            $table->enum('categorie', ['sport', 'culture', 'musique', 'autre'])->default('autre');
            $table->enum('statut', ['actif', 'annule', 'termine'])->default('actif');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
