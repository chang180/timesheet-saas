<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_reports', function (Blueprint $table): void {
            $table->boolean('is_public')->default(false)->after('status');
            $table->timestamp('published_at')->nullable()->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_reports', function (Blueprint $table): void {
            $table->dropColumn(['is_public', 'published_at']);
        });
    }
};
