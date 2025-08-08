<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifikasi_dokumen_sop_mutus', function (Blueprint $table) {
            // Pastikan foreign key dengan nama default dihapus
            $table->dropForeign(['ajukan_dokumen_sop_mutu_id']);

            // Tambahkan ulang dengan cascade
            $table->foreign('ajukan_dokumen_sop_mutu_id')
                  ->references('id')
                  ->on('ajukan_dokumen_sop_mutus')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('verifikasi_dokumen_sop_mutus', function (Blueprint $table) {
            // Drop FK dengan cascade
            $table->dropForeign(['ajukan_dokumen_sop_mutu_id']);

            // Tambahkan FK biasa tanpa cascade (optional, tergantung kebutuhan kamu)
            $table->foreign('ajukan_dokumen_sop_mutu_id')
                  ->references('id')
                  ->on('ajukan_dokumen_sop_mutus');
        });
    }
};
