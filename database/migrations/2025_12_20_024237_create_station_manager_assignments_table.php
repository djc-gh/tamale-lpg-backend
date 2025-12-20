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
        Schema::create('station_manager_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id'); // References users.id (bigint)
            $table->uuid('station_id'); // References stations.id (uuid/char36)
            $table->unsignedBigInteger('assigned_by'); // Admin who made the assignment
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('removed_at')->nullable(); // NULL = currently assigned
            $table->string('removal_reason')->nullable(); // Why was manager removed
            $table->timestamps();

            // Foreign keys
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('station_id')->references('id')->on('stations')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes for common queries
            $table->index('station_id');
            $table->index('manager_id');
            $table->index(['station_id', 'removed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_manager_assignments');
    }
};
