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
        Schema::create('daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->date('challenge_date');
            $table->unsignedTinyInteger('position');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'challenge_date', 'position']);
            $table->unique(['user_id', 'challenge_date', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_challenges');
    }
};
