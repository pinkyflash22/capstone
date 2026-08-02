<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: the base markers migration now includes these category values,
        // which keeps fresh PostgreSQL/Supabase migrations portable.
    }

    public function down(): void
    {
        // No-op for the same reason as up().
    }
};
