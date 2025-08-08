<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajukan_dokumen_sop_mutus', function (Blueprint $table) {
            $table->integer('nomor_revisi')->default(0)->after('nama_asli_dokumen');
        });
    }

    public function down(): void
    {
        Schema::table('ajukan_dokumen_sop_mutus', function (Blueprint $table) {
            $table->dropColumn('nomor_revisi');
        });
    }
};