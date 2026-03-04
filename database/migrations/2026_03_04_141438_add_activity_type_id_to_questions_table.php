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
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('activity_type_id')
                ->nullable()
                ->after('class_id')
                ->constrained('activity_types');
        });

        $grammarTypeId = DB::table('activity_types')->where('slug', 'gramatica')->value('id');
        $textInterpretationTypeId = DB::table('activity_types')->where('slug', 'interpretacao-textual')->value('id');

        if ($grammarTypeId !== null && $textInterpretationTypeId !== null) {
            DB::table('questions')->whereRaw('id % 2 = 1')->update([
                'activity_type_id' => $grammarTypeId,
            ]);

            DB::table('questions')->whereRaw('id % 2 = 0')->update([
                'activity_type_id' => $textInterpretationTypeId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_type_id');
        });
    }
};
