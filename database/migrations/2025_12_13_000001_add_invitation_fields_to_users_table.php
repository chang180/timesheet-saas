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
        Schema::table('users', function (Blueprint $table) {
            $table->string('invitation_token', 64)->nullable()->unique()->after('registered_via');
            $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'invitation_token',
                'invitation_sent_at',
                'invitation_accepted_at',
            ]);
        });
    }
};
