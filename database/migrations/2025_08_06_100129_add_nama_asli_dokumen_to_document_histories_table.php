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
        Schema::table('document_histories', function (Blueprint $table) {
            // Tambahkan kolom nama_asli_dokumen, bisa nullable karena mungkin ada riwayat lama yang tidak punya ini
            $table->string('nama_asli_dokumen')->nullable()->after('documentable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_histories', function (Blueprint $table) {
            $table->dropColumn('nama_asli_dokumen');
        });
    }
};
