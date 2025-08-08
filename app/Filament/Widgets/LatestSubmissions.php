<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\AjukanDokumenSopMutu;

class LatestSubmissions extends BaseWidget
{
    protected static ?string $heading = '5 Pengajuan Terbaru';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(AjukanDokumenSopMutu::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('user.nama')->label('Pengaju'),
                Tables\Columns\TextColumn::make('nama_asli_dokumen')->label('Nama Dokumen'),
                Tables\Columns\TextColumn::make('substansi.substansi')->label('Substansi'),
                Tables\Columns\BadgeColumn::make('statusProgress.status')->label('Status')->colors([
                    'success' => 'Diterima',
                    'danger' => 'Ditolak',
                    'warning' => fn ($state): bool => in_array($state, ['Revisi', 'Menunggu di Review', 'Menunggu di revisi']),
                    'info' => 'Review',
                ]),
                Tables\Columns\TextColumn::make('waktu_pengajuan')->label('Waktu Pengajuan')->dateTime('d M Y, H:i'),
            ])
            ->paginated(false);
    }
}