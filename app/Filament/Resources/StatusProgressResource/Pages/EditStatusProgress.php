<?php

namespace App\Filament\Resources\StatusProgressResource\Pages;

use App\Filament\Resources\StatusProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatusProgress extends EditRecord
{
    protected static string $resource = StatusProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
