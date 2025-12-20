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
        // Alter the existing users table to add LPG Tamale specific columns
        Schema::table('users', function (Blueprint $table) {
            // Add role column if it doesn't exist
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'station'])->default('admin')->after('email');
            }
            
            // Add station_id column if it doesn't exist (using UUID string type to match stations table)
            if (!Schema::hasColumn('users', 'station_id')) {
                $table->string('station_id', 36)->nullable()->after('role');
                $table->foreign('station_id')->references('id')->on('stations')->onDelete('set null');
            }
            
            // Add is_active column if it doesn't exist
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('station_id');
            }

            // Add indexes
            $table->index('email', 'idx_users_email');
            $table->index('role', 'idx_users_role');
            $table->index('station_id', 'idx_users_station_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_station_id');
            $table->dropForeign(['station_id']);
            $table->dropColumn(['role', 'station_id', 'is_active']);
        });
    }
};
