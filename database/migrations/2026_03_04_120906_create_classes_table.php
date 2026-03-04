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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('classes')->insert([
            ['name' => '6º série', 'created_at' => $now, 'updated_at' => $now],
            ['name' => '7º série', 'created_at' => $now, 'updated_at' => $now],
            ['name' => '8º série', 'created_at' => $now, 'updated_at' => $now],
            ['name' => '9º série', 'created_at' => $now, 'updated_at' => $now],
            ['name' => '1º ano', 'created_at' => $now, 'updated_at' => $now],
            ['name' => '2º ano', 'created_at' => $now, 'updated_at' => $now],
            ['name' => '3º ano', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
