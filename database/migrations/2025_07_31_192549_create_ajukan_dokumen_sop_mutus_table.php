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
        Schema::create('ajukan_dokumen_sop_mutus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Pengaju
            $table->foreignId('substansi_id')->constrained('substansis');
            $table->foreignId('sub_kategori_sop_id')->constrained('sub_kategori_sops');
            $table->string('nama_dokumen');
            $table->timestamp('waktu_pengajuan');
            $table->text('keterangan')->nullable(); // contoh: "Dokumen baru", "Revisi dokumen lama"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajukan_dokumen_sop_mutus');
    }
};