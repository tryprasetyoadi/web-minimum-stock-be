<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('jenis');
            $table->string('merk');
            $table->unsignedInteger('qty');
            $table->string('delivery_by'); // contoh: Udara, Darat
            $table->text('alamat_tujuan');
            $table->foreignId('pic_user_id')->constrained('users');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->string('status')->default('Submitted'); // Submitted, On Going, Completed, Cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};