<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_action_plan_checklists', function (Blueprint $table) {
            $table->json('checklist_item_reviews')
                ->nullable()
                ->after('checklist_items');
        });
    }

    public function down(): void
    {
        Schema::table('ai_action_plan_checklists', function (Blueprint $table) {
            $table->dropColumn('checklist_item_reviews');
        });
    }
};
