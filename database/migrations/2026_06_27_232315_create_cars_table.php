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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 50);
            $table->integer('year');
            $table->string('number_plate', 10)->unique();
            $table->string('fuel_type', 20);
            $table->string('transmission', 20);
            $table->decimal('price_per_minute', 6, 2);
            $table->string('status', 20)->default('available');
            $table->string('image_url', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
