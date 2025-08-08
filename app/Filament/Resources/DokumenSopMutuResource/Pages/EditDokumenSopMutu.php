<?php

namespace App\Filament\Resources\DokumenSopMutuResource\Pages;

use App\Filament\Resources\DokumenSopMutuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDokumenSopMutu extends EditRecord
{
    protected static string $resource = DokumenSopMutuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
