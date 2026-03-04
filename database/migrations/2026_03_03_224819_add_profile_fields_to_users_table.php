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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('state')->nullable()->after('phone');
            $table->string('city')->nullable()->after('state');
            $table->string('school')->nullable()->after('city');
            $table->string('class')->nullable()->after('school');
            $table->string('shift')->nullable()->after('class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_uuid_unique');
            $table->dropColumn([
                'uuid',
                'first_name',
                'last_name',
                'phone',
                'state',
                'city',
                'school',
                'class',
                'shift',
            ]);
        });
    }
};
