<?php

namespace App\Filament\Resources\SubKategoriSopResource\Pages;

use App\Filament\Resources\SubKategoriSopResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubKategoriSop extends EditRecord
{
    protected static string $resource = SubKategoriSopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
