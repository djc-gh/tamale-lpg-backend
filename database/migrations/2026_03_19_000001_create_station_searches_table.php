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
        Schema::create('station_searches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('user', 'searchable'); // nullable UUID user_id + searchable_type
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_km')->default(5);
            $table->boolean('available_only')->default(false);
            $table->string('client_ip', 45); // Support IPv6
            $table->string('user_agent')->nullable();
            $table->unsignedInteger('results_count')->default(0); // How many stations found
            $table->timestamps();

            // Indexes for analytics queries
            $table->index(['latitude', 'longitude'], 'idx_searches_location');
            $table->index('created_at', 'idx_searches_created_at');
            $table->index('user_id', 'idx_searches_user_id');
            $table->index(['latitude', 'longitude', 'created_at'], 'idx_searches_location_time');
            $table->index('radius_km', 'idx_searches_radius');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_searches');
    }
};
