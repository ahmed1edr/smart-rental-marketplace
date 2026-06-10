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
    Schema::create('property_images', function (Blueprint $table) {
        $table->id();
        // الربط مع العقار
        $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();

        $table->string('image_path'); // مسار الصورة في السيرفر
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};
