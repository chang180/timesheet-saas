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
            $table->foreignId('weekly_report_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('title');
            $table->text('content')->nullable();
            $table->decimal('hours_spent', 5, 2)->default(0);
            $table->string('issue_reference', 191)->nullable();
            $table->boolean('is_billable')->default(false);
            $table->json('tags')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['weekly_report_id', 'sort_order']);
            $table->index(['issue_reference']);
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
