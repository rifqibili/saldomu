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
        Schema::create('dokumen_sop_mutus', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_sop')->unique();
            $table->string('nama_sop');
            $table->foreignId('substansi_id')->constrained('substansis'); // Relasi ke tabel substansis
            $table->date('tanggal_terbit');
            $table->integer('nomor_revisi')->default(0);
            $table->foreignId('status_id')->constrained('statuses');
            $table->foreignId('sub_kategori_sop_id')->constrained('sub_kategori_sops');
            $table->timestamp('waktu_pengajuan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_sop_mutus');
    }
};