<?php

namespace App\Filament\Resources\DocumentHistoryResource\Pages;

use App\Filament\Resources\DocumentHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentHistory extends ViewRecord
{
    protected static string $resource = DocumentHistoryResource::class;

    // Anda bisa menambahkan actions di sini jika diperlukan,
    // misalnya Actions\EditAction::make() jika ingin bisa mengedit dari halaman view (meskipun untuk riwayat biasanya tidak diedit)
    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(), // Biasanya tidak diperlukan untuk riwayat
        ];
    }
}
