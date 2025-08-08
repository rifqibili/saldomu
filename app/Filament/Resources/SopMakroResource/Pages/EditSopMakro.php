<?php

namespace App\Filament\Resources\SopMakroResource\Pages;

use App\Filament\Resources\SopMakroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSopMakro extends EditRecord
{
    protected static string $resource = SopMakroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
