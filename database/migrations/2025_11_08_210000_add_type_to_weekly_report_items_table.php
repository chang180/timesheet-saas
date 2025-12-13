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
        Schema::table('weekly_report_items', function (Blueprint $table) {
            $table->string('type', 32)
                ->default('current_week')
                ->after('weekly_report_id');
            $table->decimal('planned_hours', 5, 2)
                ->nullable()
                ->after('hours_spent');

            $table->index(['weekly_report_id', 'type', 'sort_order'], 'weekly_report_items_report_type_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_report_items', function (Blueprint $table) {
            $table->dropIndex('weekly_report_items_report_type_order_index');
            $table->dropColumn(['type', 'planned_hours']);
        });
    }
};

