<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('questions', 'correct_alternative')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->string('correct_alternative', 1)->default('a')->after('alternative_d');
            });
        }

        DB::table('questions')
            ->orderBy('id')
            ->select('id')
            ->chunkById(200, function ($questions): void {
                foreach ($questions as $question) {
                    $correctAlternative = match ($question->id % 4) {
                        1 => 'a',
                        2 => 'b',
                        3 => 'c',
                        default => 'd',
                    };

                    DB::table('questions')
                        ->where('id', $question->id)
                        ->update(['correct_alternative' => $correctAlternative]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('questions', 'correct_alternative')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('correct_alternative');
        });
    }
};
