<?php

namespace App\Filament\Resources\VerifikasiDokumenSopMutuResource\Pages;

use App\Filament\Resources\VerifikasiDokumenSopMutuResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\AjukanDokumenSopMutu; // Import model AjukanDokumenSopMutu

class CreateVerifikasiDokumenSopMutu extends CreateRecord
{
    protected static string $resource = VerifikasiDokumenSopMutuResource::class;

    protected function afterCreate(): void
    {
        $verifikasiRecord = $this->record;
        
        $ajukanRecord = AjukanDokumenSopMutu::find($verifikasiRecord->ajukan_dokumen_sop_mutu_id);
        
        if ($ajukanRecord) {
            $ajukanRecord->status_progress_id = $verifikasiRecord->status_progress_id;
            $ajukanRecord->save();
        }
    }
}