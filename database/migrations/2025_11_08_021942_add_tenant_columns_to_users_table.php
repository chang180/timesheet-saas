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
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('division_id')->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->string('role', 32)->default('member')->after('remember_token');
            $table->string('position')->nullable()->after('role');
            $table->string('phone', 32)->nullable()->after('position');
            $table->string('timezone')->default('Asia/Taipei')->after('phone');
            $table->string('locale', 8)->default('zh_TW')->after('timezone');
            $table->string('registered_via', 32)->nullable()->after('locale');
            $table->timestamp('onboarded_at')->nullable()->after('registered_via');
            $table->timestamp('last_active_at')->nullable()->after('onboarded_at');

            $table->index(['company_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_company_id_role_index');

            $table->dropConstrainedForeignId('team_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('division_id');
            $table->dropConstrainedForeignId('company_id');

            $table->dropColumn([
                'role',
                'position',
                'phone',
                'timezone',
                'locale',
                'registered_via',
                'onboarded_at',
                'last_active_at',
            ]);
        });
    }
};
