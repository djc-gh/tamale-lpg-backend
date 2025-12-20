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
        // Add temporary uuid column if it doesn't already exist
        if (!Schema::hasColumn('station_manager_assignments', 'uuid')) {
            Schema::table('station_manager_assignments', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }

        // Generate UUIDs for all existing records (if not already set)
        DB::statement('UPDATE station_manager_assignments SET uuid = UUID() WHERE uuid IS NULL');

        // Drop foreign keys that reference this table and that we reference
        $constraints = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME='station_manager_assignments' AND REFERENCED_TABLE_NAME='users'");
        
        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE station_manager_assignments DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
        }

        // Change manager_id and assigned_by to VARCHAR(36) to match users.id type
        DB::statement('ALTER TABLE station_manager_assignments MODIFY manager_id VARCHAR(36) NOT NULL');
        DB::statement('ALTER TABLE station_manager_assignments MODIFY assigned_by VARCHAR(36) NOT NULL');

        // Remove AUTO_INCREMENT from id column first, then change the type
        DB::statement('ALTER TABLE station_manager_assignments MODIFY id BIGINT UNSIGNED NOT NULL');
        
        // Drop primary key
        DB::statement('ALTER TABLE station_manager_assignments DROP PRIMARY KEY');
        
        // Drop the old id column and rename uuid to id
        DB::statement('ALTER TABLE station_manager_assignments DROP COLUMN id');
        DB::statement('ALTER TABLE station_manager_assignments CHANGE COLUMN uuid id CHAR(36) NOT NULL');
        
        // Add id as primary key
        DB::statement('ALTER TABLE station_manager_assignments ADD PRIMARY KEY (id)');

        // Recreate foreign key constraints
        DB::statement('ALTER TABLE station_manager_assignments ADD CONSTRAINT station_manager_assignments_manager_id_foreign FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE station_manager_assignments ADD CONSTRAINT station_manager_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT');
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
