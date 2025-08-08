<?php

namespace Database\Seeders;

use App\Models\Substansi;
use App\Models\Status;
use App\Models\StatusProgress;
use App\Models\SubKategoriSop;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // Substansi
        Substansi::firstOrCreate(['substansi' => 'Infokom']);
        Substansi::firstOrCreate(['substansi' => 'Tata Usaha']);
        Substansi::firstOrCreate(['substansi' => 'Pemeriksaan']);
        Substansi::firstOrCreate(['substansi' => 'Pengujian']);
        Substansi::firstOrCreate(['substansi' => 'Penindakan']);

        // Status
        Status::firstOrCreate(['status' => 'Aktif']);
        Status::firstOrCreate(['status' => 'Non-aktif']);
        
        // Status Progress
        StatusProgress::firstOrCreate(['status' => 'Diterima']);
        StatusProgress::firstOrCreate(['status' => 'Ditolak']);
        StatusProgress::firstOrCreate(['status' => 'Revisi']);
        StatusProgress::firstOrCreate(['status' => 'Menunggu di Revisi']);

        // Sub Kategori SOP
        SubKategoriSop::firstOrCreate(['jenis_sop' => 'SOP Makro']);
        SubKategoriSop::firstOrCreate(['jenis_sop' => 'SOP Mikro']);
    }
}