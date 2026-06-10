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
    Schema::create('reviews', function (Blueprint $table) {
        $table->id();

        // الربط مع الكليان (المستخدم) والعقار
        $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();

        $table->integer('rating'); // من 1 لـ 5 نجوم
        $table->text('comment')->nullable();

        $table->timestamps();

        // منع الكليان من تقييم نفس العقار أكثر من مرة
        $table->unique(['client_id', 'property_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
