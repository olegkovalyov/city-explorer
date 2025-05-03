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
        Schema::create('favorite_cities', function (Blueprint $table) {
            $table->id();
            // Foreign key to link with the users table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('city_name');
            // Store coordinates for easy map centering/weather lookup
            $table->decimal('latitude', 10, 7); // Precision 10, Scale 7 is good for lat/lng
            $table->decimal('longitude', 10, 7); // Precision 10, Scale 7 is good for lat/lng
            $table->timestamps();

            // Add a unique constraint to prevent duplicate entries per user
            $table->unique(['user_id', 'city_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_cities');
    }
};
