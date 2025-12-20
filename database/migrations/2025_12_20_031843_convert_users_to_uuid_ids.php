<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add temporary uuid column if it doesn't exist
            if (!Schema::hasColumn('users', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
        });

        // Generate UUIDs for all existing users if not already set
        DB::statement('UPDATE users SET uuid = UUID() WHERE uuid IS NULL');

        // Drop foreign key constraints that reference users(id) using raw SQL
        // We check if they exist before trying to drop them
        $constraints = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME='station_manager_assignments' AND REFERENCED_TABLE_NAME='users'");
        
        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE station_manager_assignments DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
        }
        
        if (Schema::hasTable('station_availability_log')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME='station_availability_log' AND REFERENCED_TABLE_NAME='users'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE station_availability_log DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }
        
        if (Schema::hasTable('price_history')) {
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME='price_history' AND REFERENCED_TABLE_NAME='users'");
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE price_history DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            }
        }

        // Change id column type and uuid to id
        Schema::table('users', function (Blueprint $table) {
            // Modify the id column to be a string (UUID)
            $table->string('id', 36)->change();
        });

        // Update id values with uuid values
        DB::statement('UPDATE users SET id = uuid');

        // Drop the temporary uuid column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        // Now update foreign key columns in related tables
        DB::statement('ALTER TABLE station_manager_assignments MODIFY manager_id VARCHAR(36) NOT NULL');
        DB::statement('ALTER TABLE station_manager_assignments MODIFY assigned_by VARCHAR(36) NOT NULL');
        DB::statement('ALTER TABLE station_availability_log MODIFY changed_by VARCHAR(36) NOT NULL');
        DB::statement('ALTER TABLE price_history MODIFY updated_by VARCHAR(36) NOT NULL');

        // Recreate foreign key constraints
        DB::statement('ALTER TABLE station_manager_assignments ADD CONSTRAINT station_manager_assignments_manager_id_foreign FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE station_manager_assignments ADD CONSTRAINT station_manager_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE station_availability_log ADD CONSTRAINT station_availability_log_changed_by_foreign FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE price_history ADD CONSTRAINT price_history_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE RESTRICT');

        // Update personal_access_tokens
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('tokenable_id', 36)->change();
            // Note: personal_access_tokens doesn't have a foreign key constraint on tokenable_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is irreversible in production
        // UUID data cannot be safely converted back to bigint auto-increment IDs
        // Manual intervention would be required if rollback is necessary
    }
};
