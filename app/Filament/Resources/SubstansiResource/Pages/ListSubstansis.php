<?php

namespace App\Filament\Resources\SubstansiResource\Pages;

use App\Filament\Resources\SubstansiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubstansis extends ListRecords
{
    protected static string $resource = SubstansiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
