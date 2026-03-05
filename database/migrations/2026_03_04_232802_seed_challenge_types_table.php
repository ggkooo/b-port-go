<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $challengeTypes = [
            ['name' => 'minutes', 'description' => 'Desafio baseado em minutos de estudo'],
            ['name' => 'lessons', 'description' => 'Desafio baseado em lições completadas'],
            ['name' => 'exercises', 'description' => 'Desafio baseado em exercícios resolvidos'],
            ['name' => 'streak_exercises', 'description' => 'Desafio baseado em exercícios acertados em sequência'],
            ['name' => 'lessons_no_tip', 'description' => 'Desafio baseado em lições completadas sem usar dica'],
        ];

        DB::table('challenge_types')->insert(
            array_map(
                fn (array $type): array => array_merge($type, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                $challengeTypes
            )
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('challenge_types')->truncate();
    }
};
