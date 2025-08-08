<?php

namespace App\Filament\Resources\LaporanAuditResource\Pages;

use App\Filament\Resources\LaporanAuditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaporanAudit extends EditRecord
{
    protected static string $resource = LaporanAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
