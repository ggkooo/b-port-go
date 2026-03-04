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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('statement');
            $table->string('alternative_a');
            $table->string('alternative_b');
            $table->string('alternative_c');
            $table->string('alternative_d');
            $table->string('correct_alternative', 1);
            $table->text('tip');
            $table->foreignId('difficulty_id')->constrained('difficulties');
            $table->foreignId('class_id')->constrained('classes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
