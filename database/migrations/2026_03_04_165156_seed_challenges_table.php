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
        $now = now();

        $challenges = [
            ['name' => 'Estude por 10 minutos', 'unit' => 'minutes', 'target_value' => 10, 'xp_reward' => 30],
            ['name' => 'Estude por 15 minutos', 'unit' => 'minutes', 'target_value' => 15, 'xp_reward' => 30],
            ['name' => 'Faça 2 lições', 'unit' => 'lessons', 'target_value' => 2, 'xp_reward' => 30],
            ['name' => 'Faça 3 lições', 'unit' => 'lessons', 'target_value' => 3, 'xp_reward' => 30],
            ['name' => 'Resolva 5 exercícios', 'unit' => 'exercises', 'target_value' => 5, 'xp_reward' => 30],
            ['name' => 'Resolva 8 exercícios', 'unit' => 'exercises', 'target_value' => 8, 'xp_reward' => 30],
            ['name' => 'Acerte 3 exercícios seguidos', 'unit' => 'streak_exercises', 'target_value' => 3, 'xp_reward' => 30],
            ['name' => 'Acerte 5 exercícios seguidos', 'unit' => 'streak_exercises', 'target_value' => 5, 'xp_reward' => 50],
            ['name' => 'Faça 2 lições sem pular dica', 'unit' => 'lessons_no_tip', 'target_value' => 2, 'xp_reward' => 50],
            ['name' => 'Estude por 20 minutos', 'unit' => 'minutes', 'target_value' => 20, 'xp_reward' => 50],
            ['name' => 'Acerte 5 exercícios seguidos em 2 lições', 'unit' => 'streak_exercises', 'target_value' => 5, 'xp_reward' => 50],
        ];

        DB::table('challenges')->insert(
            array_map(
                fn (array $challenge): array => array_merge($challenge, [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                $challenges
            )
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $challengeNames = [
            'Estude por 10 minutos',
            'Estude por 15 minutos',
            'Faça 2 lições',
            'Faça 3 lições',
            'Resolva 5 exercícios',
            'Resolva 8 exercícios',
            'Acerte 3 exercícios seguidos',
            'Acerte 5 exercícios seguidos',
            'Faça 2 lições sem pular dica',
            'Estude por 20 minutos',
            'Acerte 5 exercícios seguidos em 2 lições',
        ];

        DB::table('challenges')
            ->whereIn('name', $challengeNames)
            ->delete();
    }
};
