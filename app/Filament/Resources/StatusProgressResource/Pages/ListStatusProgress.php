<?php

namespace App\Filament\Resources\StatusProgressResource\Pages;

use App\Filament\Resources\StatusProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStatusProgress extends ListRecords
{
    protected static string $resource = StatusProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
