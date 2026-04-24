<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Allow weekly reports to detach from a company when their owner returns to
     * personal status (company dissolution). The foreign key is rebuilt with
     * nullOnDelete so detached reports survive as personal weekly reports.
     */
    public function up(): void
    {
        Schema::table('weekly_reports', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('weekly_reports', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->change();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_reports', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('weekly_reports', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable(false)
                ->change();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }
};
