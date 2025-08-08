<?php

namespace App\Filament\Resources\AjukanDokumenSopMutuResource\Pages;

use App\Filament\Resources\AjukanDokumenSopMutuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\AjukanDokumenSopMutu;   // <-- TAMBAHKAN BARIS INI
use App\Models\StatusProgress;      // <-- TAMBAHKAN BARIS INI

class EditAjukanDokumenSopMutu extends EditRecord
{
    protected static string $resource = AjukanDokumenSopMutuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(function (AjukanDokumenSopMutu $record) {
                    $statusIdMenungguRevisi = StatusProgress::where('status', 'Menunggu di Review')->value('id');
                    return $record->status_progress_id === $statusIdMenungguRevisi;
                }),
        ];
    }
}