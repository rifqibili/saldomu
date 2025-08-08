<?php

namespace App\Filament\Resources\SertifikatResource\Pages;

use App\Filament\Resources\SertifikatResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification; // Pastikan ini diimpor

class CreateSertifikat extends CreateRecord
{
    protected static string $resource = SertifikatResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record; // Ini adalah record Sertifikat yang baru dibuat

        // Catat riwayat untuk pembuatan Sertifikat
        $record->recordHistory(
            'Dibuat', // Aksi
            'Sertifikat baru telah dibuat.', // Deskripsi
            null, // Old Status (tidak ada untuk pembuatan awal)
            null, // New Status (tidak ada perubahan status spesifik di sini)
            $record->nama_asli_dokumen // Nama asli dokumen
        );

        Notification::make()
            ->title('Sertifikat Berhasil Dibuat')
            ->body("Sertifikat '{$record->nama_asli_dokumen}' telah berhasil dibuat dan dicatat dalam riwayat.")
            ->success()
            ->send();
    }
}
