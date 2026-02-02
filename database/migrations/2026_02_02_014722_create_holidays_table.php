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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name')->nullable();
            $table->boolean('is_holiday')->default(true);
            $table->string('category')->nullable()->comment('national, weekday_off, makeup_workday, etc.');
            $table->text('note')->nullable();
            $table->string('source')->default('ntpc')->comment('ntpc = New Taipei City Open Data');
            $table->boolean('is_workday_override')->default(false)->comment('True for makeup workdays');
            $table->unsignedSmallInteger('iso_week')->index();
            $table->unsignedSmallInteger('iso_week_year')->index();
            $table->timestamps();

            $table->index(['iso_week_year', 'iso_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
