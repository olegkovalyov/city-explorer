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
        Schema::create('favorite_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key to users table
            $table->string('fsq_id'); // Foursquare ID
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable(); // Precision 10, Scale 7 for lat/lng
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo_url')->nullable(); // URL for the main photo
            $table->string('category')->nullable();
            $table->string('category_icon')->nullable();
            $table->timestamps();

            // Ensure a user cannot favorite the same place twice
            $table->unique(['user_id', 'fsq_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_places');
    }
};
