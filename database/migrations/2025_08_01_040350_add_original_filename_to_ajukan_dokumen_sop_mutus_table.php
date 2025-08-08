<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajukan_dokumen_sop_mutus', function (Blueprint $table) {
            $table->string('nama_asli_dokumen')->nullable()->after('nama_dokumen');
        });
    }

    public function down(): void
    {
        Schema::table('ajukan_dokumen_sop_mutus', function (Blueprint $table) {
            $table->dropColumn('nama_asli_dokumen');
        });
    }
};