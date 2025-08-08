<?php

namespace App\Filament\Resources\DokumenSopMutuResource\Pages;

use App\Filament\Resources\DokumenSopMutuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\DokumenSopMutu;
use App\Models\Status;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListDokumenSopMutus extends ListRecords
{
    protected static string $resource = DokumenSopMutuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make('export')
                ->exports([
                    ExcelExport::make('dokumen-sop-mutu')
                        ->fromTable(),
                ]),
            // Tambahkan tombol Copy di sini
            Actions\Action::make('copy')
                ->label('Copy')
                ->icon('heroicon-o-clipboard')
                ->action(function () {
                    // Aksi ini hanya akan menampilkan notifikasi.
                    // Anda bisa menggantinya dengan logika untuk menyalin data ke clipboard.
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
        // Ambil ID status dari tabel Status
        $statusAktifId = Status::where('status', 'Aktif')->value('id');
        $statusNonAktifId = Status::where('status', 'Non-aktif')->value('id');

        return [
            'Semua' => Tab::make()
                ->badge(DokumenSopMutu::count()),

            'Aktif' => Tab::make()
                ->badge(DokumenSopMutu::where('status_id', $statusAktifId)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_id', $statusAktifId)),

            'Non-aktif' => Tab::make()
                ->badge(DokumenSopMutu::where('status_id', $statusNonAktifId)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_id', $statusNonAktifId)),
        ];
    }
}