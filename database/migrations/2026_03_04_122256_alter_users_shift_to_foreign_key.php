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
        DB::table('users')->update(['shift' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('shift')->nullable()->change();
            $table->foreign('shift')->references('id')->on('shifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shift']);
            $table->string('shift')->nullable()->change();
        });
    }
};
