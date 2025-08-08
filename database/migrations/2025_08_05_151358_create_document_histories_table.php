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
        Schema::create('document_histories', function (Blueprint $table) {
            $table->id();
            // ID dokumen yang terkait (bisa dari AjukanDokumenSopMutu atau DokumenSopMutu)
            // Kita akan menggunakan morphs untuk fleksibilitas
            $table->morphs('documentable'); // Ini akan membuat documentable_id dan documentable_type

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Pengguna yang melakukan aksi
            $table->string('action'); // Jenis aksi: 'Diajukan', 'Direview', 'Diterima', 'Ditolak', 'Direvisi', 'Dihapus'
            $table->text('description')->nullable(); // Deskripsi detail aksi
            $table->string('old_status')->nullable(); // Status sebelumnya (jika ada perubahan status)
            $table->string('new_status')->nullable(); // Status baru (jika ada perubahan status)
            $table->timestamps(); // created_at (waktu kejadian) dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_histories');
    }
};
