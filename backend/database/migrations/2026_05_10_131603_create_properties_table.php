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
    Schema::create('properties', function (Blueprint $table) {
        $table->id();
        // الربط مع جدول users (المالك)
        $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

        $table->string('titre');
        $table->text('description')->nullable();
        $table->decimal('prix_par_nuit', 10, 2);
        $table->string('localisation');
        $table->string('type'); // مثلا: appartement, villa, service
        $table->integer('max_guests')->default(1);
        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
