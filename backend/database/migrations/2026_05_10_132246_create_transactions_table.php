<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();

        // الربط مع الحجز
        $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();

        $table->decimal('montant', 10, 2);
        $table->string('methode_paiement')->default('cash'); // cash, stripe
        $table->string('status')->default('pending'); // pending, success, cancelled

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
