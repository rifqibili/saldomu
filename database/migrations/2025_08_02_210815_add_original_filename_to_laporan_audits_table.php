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
    Schema::table('laporan_audits', function (Blueprint $table) {
        $table->string('nama_asli_dokumen')->after('dokumen')->nullable();
    });
}

public function down(): void
{
    Schema::table('laporan_audits', function (Blueprint $table) {
        $table->dropColumn('nama_asli_dokumen');
    });
}
};
