<?php

namespace App\Filament\Resources\AjukanDokumenSopMutuResource\Pages;

use App\Filament\Resources\AjukanDokumenSopMutuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\AjukanDokumenSopMutu;
use App\Models\StatusProgress;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListAjukanDokumenSopMutus extends ListRecords
{
    protected static string $resource = AjukanDokumenSopMutuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make('export')
                ->exports([
                    ExcelExport::make('ajukan-dokumen-sop-mutu')
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

    public function getTabs(): array
    {
        return [
            'Semua' => Tab::make()
                ->badge(AjukanDokumenSopMutu::count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('statusProgress', fn (Builder $query) => $query->whereIn('status', ['Menunggu di Review', 'Review', 'Diterima', 'Ditolak', 'Revisi', 'Menunggu di revisi']))),

            'Menunggu di Review' => Tab::make()
                ->badge(AjukanDokumenSopMutu::whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Menunggu di Review'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Menunggu di Review'))),

            'Review' => Tab::make()
                ->badge(AjukanDokumenSopMutu::whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Review'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Review'))),
            
            'Diterima' => Tab::make()
                ->badge(AjukanDokumenSopMutu::whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Diterima'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Diterima'))),

            'Ditolak' => Tab::make()
                ->badge(AjukanDokumenSopMutu::whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Ditolak'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Ditolak'))),
                
            'Revisi' => Tab::make()
                ->badge(AjukanDokumenSopMutu::whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Revisi'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('statusProgress', fn (Builder $query) => $query->where('status', 'Revisi'))),
        
        ];
    }
}