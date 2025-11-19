<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('last_read_id')->nullable();
            $table->timestamps();

            $table->foreign('last_read_id')->references('id')->on('shipment_messages')->onDelete('set null');
            $table->unique(['shipment_id', 'user_id']);
            $table->index(['shipment_id', 'last_read_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_message_reads');
    }
};