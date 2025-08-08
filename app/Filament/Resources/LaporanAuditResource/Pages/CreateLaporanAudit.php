<?php

namespace App\Filament\Resources\LaporanAuditResource\Pages;

use App\Filament\Resources\LaporanAuditResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification; // Pastikan ini diimpor

class CreateLaporanAudit extends CreateRecord
{
    protected static string $resource = LaporanAuditResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record; // Ini adalah record LaporanAudit yang baru dibuat

        // Catat riwayat untuk pembuatan Laporan Audit
        $record->recordHistory(
            'Dibuat', // Aksi
            'Laporan Audit baru telah dibuat.', // Deskripsi
            null, // Old Status (tidak ada untuk pembuatan awal)
            null, // New Status (tidak ada perubahan status spesifik di sini)
            $record->nama_asli_dokumen // Nama asli dokumen
        );

        Notification::make()
            ->title('Laporan Audit Berhasil Dibuat')
            ->body("Laporan Audit '{$record->nama_asli_dokumen}' telah berhasil dibuat dan dicatat dalam riwayat.")
            ->success()
            ->send();
    }
}
