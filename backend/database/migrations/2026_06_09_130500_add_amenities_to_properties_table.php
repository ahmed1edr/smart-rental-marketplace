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
        Schema::table('properties', function (Blueprint $table) {
            $table->integer('rooms_count')->default(1)->after('max_guests');
            $table->boolean('has_wifi')->default(false)->after('rooms_count');
            $table->boolean('has_pool')->default(false)->after('has_wifi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['rooms_count', 'has_wifi', 'has_pool']);
        });
    }
};
