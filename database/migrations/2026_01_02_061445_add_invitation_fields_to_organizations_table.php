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
        Schema::table('divisions', function (Blueprint $table) {
            $table->string('invitation_token', 64)->nullable()->unique()->after('is_active');
            $table->boolean('invitation_enabled')->default(false)->after('invitation_token');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->string('invitation_token', 64)->nullable()->unique()->after('is_active');
            $table->boolean('invitation_enabled')->default(false)->after('invitation_token');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->string('invitation_token', 64)->nullable()->unique()->after('is_active');
            $table->boolean('invitation_enabled')->default(false)->after('invitation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropColumn(['invitation_token', 'invitation_enabled']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['invitation_token', 'invitation_enabled']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['invitation_token', 'invitation_enabled']);
        });
    }
};
