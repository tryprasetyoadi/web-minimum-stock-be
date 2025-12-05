<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ta_ccan_options', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse');
            $table->string('ta_ccan');
            $table->timestamps();
            $table->unique(['warehouse', 'ta_ccan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ta_ccan_options');
    }
};

