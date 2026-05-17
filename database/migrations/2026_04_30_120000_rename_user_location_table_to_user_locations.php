<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_location') && !Schema::hasTable('user_locations')) {
            Schema::rename('user_location', 'user_locations');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_locations') && !Schema::hasTable('user_location')) {
            Schema::rename('user_locations', 'user_location');
        }
    }
};
