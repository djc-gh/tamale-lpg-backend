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
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45); // Support IPv6
            $table->string('url');
            $table->string('method')->default('GET'); // HTTP method
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // mobile, tablet, desktop
            $table->string('browser')->nullable(); // Chrome, Firefox, Safari, etc.
            $table->string('os')->nullable(); // Windows, macOS, Linux, iOS, Android
            $table->unsignedBigInteger('user_id')->nullable(); // Authenticated user
            $table->unsignedInteger('response_code')->nullable(); // HTTP response code
            $table->unsignedInteger('response_time_ms')->nullable(); // Response time in milliseconds
            $table->timestamps();

            // Indexes for fast queries
            $table->index('ip_address');
            $table->index('created_at');
            $table->index(['ip_address', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
