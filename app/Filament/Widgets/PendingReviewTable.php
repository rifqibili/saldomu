<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\AjukanDokumenSopMutu;
use App\Models\StatusProgress;

class PendingReviewTable extends BaseWidget
{
    protected static ?string $heading = 'Daftar Dokumen yang Menunggu Review';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $statusMenungguReviewId = StatusProgress::where('status', 'Menunggu di Review')->value('id');

        return $table
            ->query(
                AjukanDokumenSopMutu::query()->where('status_progress_id', $statusMenungguReviewId)->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.nama')->label('Pengaju')->searchable(),
                Tables\Columns\TextColumn::make('substansi.substansi')->label('Substansi'),
                Tables\Columns\TextColumn::make('nama_asli_dokumen')->label('Nama Dokumen'),
                Tables\Columns\TextColumn::make('waktu_pengajuan')->label('Waktu Pengajuan')->since(),
            ])
            ->actions([
                // Tambahkan aksi khusus di sini jika Anda ingin tombol "Mulai Review"
                // Contoh:
                // Tables\Actions\Action::make('startReview')
                //     ->label('Mulai Review')
                //     ->icon('heroicon-o-arrow-right-circle')
                //     ->action(function ($record) {
                //         $statusReviewId = StatusProgress::where('status', 'Review')->value('id');
                //         $record->update(['status_progress_id' => $statusReviewId]);
                //         // Tambahkan notifikasi atau redirect jika diperlukan
                //     }),
            ]);
    }
}