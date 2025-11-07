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
        Schema::create('weekly_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_report_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['current_week', 'next_week'])->default('current_week');
            $table->text('task_description');
            $table->decimal('hours_spent', 5, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('redmine_issue_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_report_items');
    }
};
