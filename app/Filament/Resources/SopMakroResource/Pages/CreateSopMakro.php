<?php

namespace App\Filament\Resources\SopMakroResource\Pages;

use App\Filament\Resources\SopMakroResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification; // Pastikan ini diimpor

class CreateSopMakro extends CreateRecord
{
    protected static string $resource = SopMakroResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record; // Ini adalah record SopMakro yang baru dibuat

        // Catat riwayat untuk pembuatan SOP Makro
        $record->recordHistory(
            'Dibuat', // Aksi
            'SOP Makro baru telah dibuat.', // Deskripsi
            null, // Old Status (tidak ada untuk pembuatan awal)
            null, // New Status (tidak ada perubahan status spesifik di sini)
            $record->nama_asli_dokumen // Nama asli dokumen
        );

        Notification::make()
            ->title('SOP Makro Berhasil Dibuat')
            ->body("SOP Makro '{$record->nama_asli_dokumen}' telah berhasil dibuat dan dicatat dalam riwayat.")
            ->success()
            ->send();
    }
}
