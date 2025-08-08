<?php

namespace App\Filament\Resources\SubstansiResource\Pages;

use App\Filament\Resources\SubstansiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubstansi extends EditRecord
{
    protected static string $resource = SubstansiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
