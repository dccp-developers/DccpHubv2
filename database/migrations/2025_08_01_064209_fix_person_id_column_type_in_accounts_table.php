<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to use raw SQL to change the column type properly
        DB::statement('ALTER TABLE accounts ALTER COLUMN person_id TYPE VARCHAR(255) USING person_id::VARCHAR(255)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to bigint (this will only work if all values are numeric)
        DB::statement('ALTER TABLE accounts ALTER COLUMN person_id TYPE BIGINT USING person_id::BIGINT');
    }
};
