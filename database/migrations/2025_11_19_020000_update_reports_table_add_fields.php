<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Tambahkan kolom-kolom spesifik agar tidak menyimpan array JSON dalam satu field
            if (!Schema::hasColumn('reports', 'type')) {
                $table->string('type')->nullable()->after('jenis');
            }
            if (!Schema::hasColumn('reports', 'qty')) {
                $table->unsignedInteger('qty')->nullable()->after('type');
            }
            if (!Schema::hasColumn('reports', 'warehouse')) {
                $table->string('warehouse')->nullable()->after('qty');
            }
            if (!Schema::hasColumn('reports', 'sender_alamat')) {
                $table->string('sender_alamat')->nullable()->after('warehouse');
            }
            if (!Schema::hasColumn('reports', 'sender_pic')) {
                $table->string('sender_pic')->nullable()->after('sender_alamat');
            }
            if (!Schema::hasColumn('reports', 'receiver_alamat')) {
                $table->string('receiver_alamat')->nullable()->after('sender_pic');
            }
            if (!Schema::hasColumn('reports', 'receiver_warehouse')) {
                $table->string('receiver_warehouse')->nullable()->after('receiver_alamat');
            }
            if (!Schema::hasColumn('reports', 'receiver_pic')) {
                $table->string('receiver_pic')->nullable()->after('receiver_warehouse');
            }
            if (!Schema::hasColumn('reports', 'tanggal_pengiriman')) {
                $table->date('tanggal_pengiriman')->nullable()->after('receiver_pic');
            }
            if (!Schema::hasColumn('reports', 'tanggal_sampai')) {
                $table->date('tanggal_sampai')->nullable()->after('tanggal_pengiriman');
            }
            if (!Schema::hasColumn('reports', 'batch')) {
                $table->string('batch')->nullable()->after('tanggal_sampai');
            }

            // Hapus kolom JSON lama jika ada
            if (Schema::hasColumn('reports', 'data')) {
                $table->dropColumn('data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Kembalikan kolom JSON lama
            if (!Schema::hasColumn('reports', 'data')) {
                $table->json('data')->nullable();
            }

            // Hapus kolom-kolom baru
            foreach ([
                'type','qty','warehouse','sender_alamat','sender_pic',
                'receiver_alamat','receiver_warehouse','receiver_pic',
                'tanggal_pengiriman','tanggal_sampai','batch'
            ] as $col) {
                if (Schema::hasColumn('reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};