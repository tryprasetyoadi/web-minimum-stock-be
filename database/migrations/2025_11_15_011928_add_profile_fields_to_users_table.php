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
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->string('address')->after('last_name');
            $table->string('phone', 25)->after('address');
            $table->unsignedBigInteger('role_id')->after('phone');
            $table->boolean('is_deleted')->default(false)->index()->after('remember_token');

            $table->foreign('role_id')->references('role_id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['first_name', 'last_name', 'address', 'phone', 'role_id', 'is_deleted']);
        });
    }
};
