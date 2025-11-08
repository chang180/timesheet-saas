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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 32)->default('active');
            $table->unsignedSmallInteger('user_limit')->default(50);
            $table->unsignedSmallInteger('current_user_count')->default(0);
            $table->string('timezone')->default('Asia/Taipei');
            $table->json('branding')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
