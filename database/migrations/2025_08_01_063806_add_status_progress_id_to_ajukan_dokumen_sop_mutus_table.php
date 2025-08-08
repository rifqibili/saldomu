<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajukan_dokumen_sop_mutus', function (Blueprint $table) {
            // Tambahkan kolom foreign key
            $table->foreignId('status_progress_id')
                  ->nullable() // Bisa null jika status awal belum ditentukan
                  ->constrained('status_progresses') // Merujuk ke tabel status_progresses
                  ->after('waktu_pengajuan'); // Posisikan setelah waktu_pengajuan
        });
    }

    public function down(): void
    {
        Schema::table('ajukan_dokumen_sop_mutus', function (Blueprint $table) {
            $table->dropForeign(['status_progress_id']);
            $table->dropColumn('status_progress_id');
        });
    }
};