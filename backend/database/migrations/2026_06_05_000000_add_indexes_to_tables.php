<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('localisation');
            $table->index('type');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index('status');
            $table->index(['client_id', 'property_id']);
            $table->index(['date_debut', 'date_fin']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['localisation']);
            $table->dropIndex(['type']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['client_id', 'property_id']);
            $table->dropIndex(['date_debut', 'date_fin']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['booking_id', 'status']);
        });
    }
};
