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
        Schema::table('daily_challenges', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'challenge_date', 'question_id']);
            $table->dropForeign(['question_id']);
            $table->dropColumn('question_id');

            $table->foreignId('challenge_id')->nullable()->after('user_id')->constrained('challenges')->nullOnDelete();
            $table->string('challenge_name')->after('challenge_id');
            $table->string('unit', 20)->after('challenge_name');
            $table->unsignedInteger('target_value')->after('unit');
            $table->unsignedInteger('current_value')->default(0)->after('target_value');
            $table->unsignedInteger('xp_reward')->after('current_value');

            $table->unique(['user_id', 'challenge_date', 'challenge_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_challenges', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'challenge_date', 'challenge_id']);
            $table->dropForeign(['challenge_id']);
            $table->dropColumn([
                'challenge_id',
                'challenge_name',
                'unit',
                'target_value',
                'current_value',
                'xp_reward',
            ]);

            $table->foreignId('question_id')->after('user_id')->constrained('questions')->cascadeOnDelete();
            $table->unique(['user_id', 'challenge_date', 'question_id']);
        });
    }
};
