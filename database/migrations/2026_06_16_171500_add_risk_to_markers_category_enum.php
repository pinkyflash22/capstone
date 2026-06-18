<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE markers
            MODIFY COLUMN category ENUM('fire', 'station', 'hydrant', 'school', 'barangay', 'bridge', 'risk', 'hazard') NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE markers
            MODIFY COLUMN category ENUM('fire', 'station', 'hydrant', 'school', 'barangay', 'bridge') NULL
        ");
    }
};
