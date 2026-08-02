<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_action_plan_checklist_items', function (Blueprint $table) {
            $table->id('ai_action_plan_checklist_item_id');
            $table->unsignedBigInteger('ai_action_plan_checklist_id');
            $table->unsignedInteger('item_order')->default(1);
            $table->text('item_text');
            $table->enum('status', ['pending', 'followed', 'not_followed'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('ai_action_plan_checklist_id', 'ai_action_plan_items_checklist_fk')
                ->references('ai_action_plan_checklist_id')
                ->on('ai_action_plan_checklists')
                ->cascadeOnDelete();

            $table->foreign('reviewed_by', 'ai_action_plan_items_reviewer_fk')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['ai_action_plan_checklist_id', 'status'], 'ai_action_plan_items_status_idx');
        });

        $checklists = DB::table('ai_action_plan_checklists')
            ->select('ai_action_plan_checklist_id', 'checklist_items', 'checklist_item_reviews', 'created_at', 'updated_at')
            ->get();

        foreach ($checklists as $checklist) {
            $items = json_decode($checklist->checklist_items ?? '[]', true);
            $reviews = json_decode($checklist->checklist_item_reviews ?? '[]', true);

            if (!is_array($items) || empty($items)) {
                continue;
            }

            $reviewMap = collect(is_array($reviews) ? $reviews : [])->keyBy(function ($review, $index) {
                return (int) ($review['item_index'] ?? $index);
            });

            $rows = [];
            foreach (array_values($items) as $index => $itemText) {
                $review = $reviewMap->get($index, []);
                $rows[] = [
                    'ai_action_plan_checklist_id' => $checklist->ai_action_plan_checklist_id,
                    'item_order' => $index + 1,
                    'item_text' => (string) $itemText,
                    'status' => !empty($review['is_checked']) ? 'followed' : 'pending',
                    'reviewed_by' => $review['checked_by'] ?? null,
                    'reviewed_at' => $review['checked_at'] ?? null,
                    'review_notes' => null,
                    'created_at' => $checklist->created_at ?? now(),
                    'updated_at' => $checklist->updated_at ?? now(),
                ];
            }

            if (!empty($rows)) {
                DB::table('ai_action_plan_checklist_items')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_action_plan_checklist_items');
    }
};
