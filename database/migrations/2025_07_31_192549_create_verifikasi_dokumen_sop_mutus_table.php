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
        Schema::create('verifikasi_dokumen_sop_mutus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users'); // Verifikator bisa null awalnya
            $table->foreignId('ajukan_dokumen_sop_mutu_id')->constrained('ajukan_dokumen_sop_mutus'); // Relasi ke pengajuan
            $table->foreignId('status_progress_id')->constrained('status_progresses');
            $table->text('keterangan')->nullable(); // Alasan revisi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifikasi_dokumen_sop_mutus');
    }
};