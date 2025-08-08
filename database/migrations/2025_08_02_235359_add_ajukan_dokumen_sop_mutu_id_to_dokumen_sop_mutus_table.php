<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen_sop_mutus', function (Blueprint $table) {
            $table->foreignId('ajukan_dokumen_sop_mutu_id')
                  ->nullable()
                  ->constrained('ajukan_dokumen_sop_mutus')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_sop_mutus', function (Blueprint $table) {
            $table->dropForeign(['ajukan_dokumen_sop_mutu_id']);
            $table->dropColumn('ajukan_dokumen_sop_mutu_id');
        });
    }
};
