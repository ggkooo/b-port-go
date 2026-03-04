<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $classes = DB::table('classes')
            ->orderBy('id')
            ->get(['id', 'name']);

        $difficulties = DB::table('difficulties')
            ->orderBy('id')
            ->get(['id', 'name']);

        $questions = [];
        $now = now();

        foreach ($classes as $class) {
            foreach ($difficulties as $difficulty) {
                for ($index = 1; $index <= 25; $index++) {
                    $questionNumber = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
                    $correctAlternative = match ($index % 4) {
                        1 => 'a',
                        2 => 'b',
                        3 => 'c',
                        default => 'd',
                    };

                    $questions[] = [
                        'statement' => "[{$class->name} - {$difficulty->name}] Questão {$questionNumber}: qual alternativa representa a melhor resposta para este enunciado?",
                        'alternative_a' => "Alternativa A da questão {$questionNumber}",
                        'alternative_b' => "Alternativa B da questão {$questionNumber}",
                        'alternative_c' => "Alternativa C da questão {$questionNumber}",
                        'alternative_d' => "Alternativa D da questão {$questionNumber}",
                        'correct_alternative' => $correctAlternative,
                        'tip' => "Dica da questão {$questionNumber}: leia com atenção o enunciado e elimine alternativas inconsistentes.",
                        'difficulty_id' => $difficulty->id,
                        'class_id' => $class->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($questions, 200) as $questionsChunk) {
            DB::table('questions')->insert($questionsChunk);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('questions')) {
            return;
        }

        DB::table('questions')->delete();
    }
};
