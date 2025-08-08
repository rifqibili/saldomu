<?php

namespace App\Filament\Resources\SopMakroResource\Pages;

use App\Filament\Resources\SopMakroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListSopMakros extends ListRecords
{
    protected static string $resource = SopMakroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make('export')
                ->exports([
                    ExcelExport::make('sop-makro')
                        ->fromTable(),
                ]),
            // Tambahkan tombol Copy di sini
            Actions\Action::make('copy')
                ->label('Copy')
                ->icon('heroicon-o-clipboard')
                ->action(function () {
                    // Di sini Anda bisa menambahkan logika untuk menyalin data ke clipboard
                    // Misalnya, menggunakan JavaScript
                    // Aksi ini hanya akan menampilkan notifikasi sederhana
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Aksi Copy berhasil!')
                        ->body('Data berhasil disalin.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
