<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifikasi_dokumen_sop_mutus', function (Blueprint $table) {
            // Drop the existing index (yang bukan foreign key)
            $table->dropIndex('verifikasi_dokumen_sop_mutus_ajukan_dokumen_sop_mutu_id_foreign');
            
            // Tambahkan foreign key constraint yang benar dengan onDelete('cascade')
            $table->foreign('ajukan_dokumen_sop_mutu_id')
                  ->references('id')
                  ->on('ajukan_dokumen_sop_mutus')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('verifikasi_dokumen_sop_mutus', function (Blueprint $table) {
            // Hapus foreign key yang baru kita buat
            $table->dropForeign(['ajukan_dokumen_sop_mutu_id']);
            
            // Tambahkan kembali index lama tanpa foreign key constraint
            $table->index('ajukan_dokumen_sop_mutu_id', 'verifikasi_dokumen_sop_mutus_ajukan_dokumen_sop_mutu_id_foreign');
        });
    }
};