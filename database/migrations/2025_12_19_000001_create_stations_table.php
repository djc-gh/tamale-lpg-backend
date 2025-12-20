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
        Schema::create('stations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('address');
            $table->string('phone', 20);
            $table->string('email')->unique();
            $table->boolean('is_available')->default(true);
            $table->decimal('price_per_kg', 10, 2)->nullable();
            $table->string('operating_hours', 100);
            $table->text('image')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();

            // Indexes
            $table->index(['latitude', 'longitude'], 'idx_stations_location');
            $table->index('is_available', 'idx_stations_is_available');
            $table->index('updated_at', 'idx_stations_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
