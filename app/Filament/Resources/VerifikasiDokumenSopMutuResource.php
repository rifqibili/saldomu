<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerifikasiDokumenSopMutuResource\Pages;
use App\Models\VerifikasiDokumenSopMutu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\StatusProgress;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\AjukanDokumenSopMutu;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\MarkdownEditor; // <-- Tambahkan ini

class VerifikasiDokumenSopMutuResource extends Resource
{
    protected static ?string $model = VerifikasiDokumenSopMutu::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Manajemen Dokumen';
    protected static ?string $navigationLabel = 'Verifikasi Dokumen';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        // Ambil semua status progress untuk opsi ToggleButtons
        $statusProgressOptions = StatusProgress::pluck('status', 'id')->toArray();

        // Definisikan warna untuk setiap status (menggunakan ID sebagai kunci)
        $statusColors = [
            StatusProgress::where('status', 'Diterima')->value('id') => 'success',
            StatusProgress::where('status', 'Ditolak')->value('id') => 'danger',
            StatusProgress::where('status', 'Review')->value('id') => 'info',
            StatusProgress::where('status', 'Revisi')->value('id') => 'warning',
            StatusProgress::where('status', 'Menunggu di Review')->value('id') => 'gray', // Atau warna lain
        ];

        return $form
            ->schema([
                // Bagian Informasi Verifikasi
                Forms\Components\Section::make('Detail Verifikasi Dokumen')
                    ->description('Periksa dan tentukan status verifikasi untuk dokumen ini.')
                    ->schema([
                        Forms\Components\Grid::make(2) // Grid 2 kolom di dalam section ini
                            ->schema([
                                Forms\Components\Select::make('ajukan_dokumen_sop_mutu_id')
                                    ->label('Dokumen Pengajuan')
                                    ->relationship('ajukanDokumenSopMutu', 'nama_asli_dokumen')
                                    ->disabled()
                                    ->required(),
                                Forms\Components\Select::make('user_id')
                                    ->label('Verifikator')
                                    ->relationship('user', 'nama')
                                    ->default(auth()->user()->id)
                                    ->disabled()
                                    ->required(),
                            ]),
                        
                        // --- ToggleButtons untuk Status Verifikasi ---
                        ToggleButtons::make('status_progress_id')
                            ->label('Status Verifikasi')
                            ->options($statusProgressOptions)
                            ->colors($statusColors) // Menggunakan mapping warna yang telah ditentukan
                            // ->grouped() // Membuat tombol terlihat menyatu
                            ->inline() // Menampilkan opsi secara horizontal
                            ->required()
                            ->columnSpanFull(), // Mengambil seluruh lebar kolom
                        // --- Akhir ToggleButtons Status Verifikasi ---

                        // --- Menggunakan MarkdownEditor untuk Keterangan ---
                        MarkdownEditor::make('keterangan')
                            ->label('Keterangan')
                            // ->rows(3) // <-- BARIS INI DIHAPUS
                            ->nullable()
                            ->columnSpanFull(),
                        // --- Akhir MarkdownEditor Keterangan ---
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ajukanDokumenSopMutu.nama_asli_dokumen')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ajukanDokumenSopMutu.user.nama')
                    ->label('Pengaju')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ajukanDokumenSopMutu.substansi.substansi')
                    ->label('Substansi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ajukanDokumenSopMutu.subKategoriSop.jenis_sop')
                    ->label('Jenis SOP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.nama')
                    ->label('Verifikator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ajukanDokumenSopMutu.nomor_revisi')
                    ->label('No. Revisi')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->ajukanDokumenSopMutu ? sprintf('%02d', $record->ajukanDokumenSopMutu->nomor_revisi) : '-'
                    ),
                TextColumn::make('ajukanDokumenSopMutu.waktu_pengajuan')
                    ->label('Waktu Pengajuan')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record->ajukanDokumenSopMutu) {
                            return '-';
                        }
                        Carbon::setLocale('id');
                        return Carbon::parse($record->ajukanDokumenSopMutu->waktu_pengajuan)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y, H:i');
                    }),
                TextColumn::make('statusProgress.status')
                    ->label('Status Progress')
                    ->badge()
                    ->colors([
                        'success' => 'Diterima',
                        'danger' => 'Ditolak',
                        'info' => 'Review',
                        'warning' => fn ($state): bool => in_array($state, ['Revisi', 'Menunggu di Review']),
                    ]),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50),
                TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_progress_id')
                    ->label('Status Verifikasi')
                    ->relationship('statusProgress', 'status'),
                SelectFilter::make('ajukanDokumenSopMutu.substansi_id')
                    ->label('Substansi Dokumen')
                    ->relationship('ajukanDokumenSopMutu.substansi', 'substansi'),
                SelectFilter::make('ajukanDokumenSopMutu.sub_kategori_sop_id')
                    ->label('Jenis SOP Dokumen')
                    ->relationship('ajukanDokumenSopMutu.subKategoriSop', 'jenis_sop'),
                SelectFilter::make('ajukan_dokumen_sop_mutu_id')
                    ->label('Nama Dokumen Pengajuan')
                    ->relationship('ajukanDokumenSopMutu', 'nama_asli_dokumen'),
                SelectFilter::make('user_id')
                    ->label('Verifikator')
                    ->relationship('user', 'nama'),
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->after(function (VerifikasiDokumenSopMutu $record) {
                            $record->recordHistory('Dihapus', 'Verifikasi dokumen telah dihapus secara soft-delete.', null, null, $record->ajukanDokumenSopMutu->nama_asli_dokumen ?? null);
                        }),
                    RestoreAction::make()
                        ->after(function (VerifikasiDokumenSopMutu $record) {
                            $record->recordHistory('Dipulihkan', 'Verifikasi dokumen telah dipulihkan dari soft-delete.', null, null, $record->ajukanDokumenSopMutu->nama_asli_dokumen ?? null);
                        }),
                    ForceDeleteAction::make()
                        ->after(function (VerifikasiDokumenSopMutu $record) {
                            $record->recordHistory('Dihapus Permanen', 'Verifikasi dokumen telah dihapus secara permanen.', null, null, $record->ajukanDokumenSopMutu->nama_asli_dokumen ?? null);
                        }),
                    Action::make('revert_status')
                        ->label('Revert Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('secondary')
                        ->visible(function (VerifikasiDokumenSopMutu $record) {
                            return in_array($record->statusProgress->status, ['Ditolak', 'Revisi']);
                        })
                        ->action(function (VerifikasiDokumenSopMutu $record) {
                            $statusMenungguDiReviewId = StatusProgress::where('status', 'Menunggu di Review')->value('id');
                            
                            $record->status_progress_id = $statusMenungguDiReviewId;
                            $record->keterangan = 'Status dikembalikan ke "Menunggu di Review" oleh verifikator.';
                            $record->save();

                            if ($record->ajukanDokumenSopMutu) {
                                $oldAjukanStatus = $record->ajukanDokumenSopMutu->statusProgress->status;
                                $record->ajukanDokumenSopMutu->status_progress_id = $statusMenungguDiReviewId;
                                $record->ajukanDokumenSopMutu->save();
                                
                                $record->ajukanDokumenSopMutu->recordHistory(
                                    'Status Dikembalikan',
                                    'Status dokumen dikembalikan ke "Menunggu di Review".',
                                    $oldAjukanStatus,
                                    StatusProgress::find($statusMenungguDiReviewId)->status,
                                    $record->ajukanDokumenSopMutu->nama_asli_dokumen
                                );
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Status Dokumen Berhasil Dikembalikan')
                                ->body('Status verifikasi dokumen telah diubah kembali menjadi "Menunggu di Review".')
                                ->success()
                                ->send();
                        }),
                    Action::make('download')
                        ->label('Download File')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (VerifikasiDokumenSopMutu $record) {
                            $filePath = $record->ajukanDokumenSopMutu->nama_dokumen ?? null;
                            $fileName = $record->ajukanDokumenSopMutu->nama_asli_dokumen ?? null;

                            if ($filePath && $fileName) {
                                return response()->download(
                                    Storage::path($filePath),
                                    $fileName
                                );
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Gagal mengunduh file.')
                                ->body('File dokumen tidak ditemukan atau tidak valid.')
                                ->danger()
                                ->send();
                        })
                        ->visible(fn (VerifikasiDokumenSopMutu $record): bool => (bool)$record->ajukanDokumenSopMutu?->nama_dokumen),
                    Action::make('lihat')
                        ->label('Lihat File')
                        ->icon('heroicon-o-eye')
                        ->url(fn (VerifikasiDokumenSopMutu $record): string => 
                            $record->ajukanDokumenSopMutu?->nama_dokumen ? Storage::url($record->ajukanDokumenSopMutu->nama_dokumen) : '#'
                        )
                        ->openUrlInNewTab()
                        ->visible(fn (VerifikasiDokumenSopMutu $record): bool => (bool)$record->ajukanDokumenSopMutu?->nama_dokumen),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dihapus (Bulk)', 'Verifikasi dokumen telah dihapus secara soft-delete dalam bulk action.', null, null, $record->ajukanDokumenSopMutu->nama_asli_dokumen ?? null);
                            }
                        }),
                    Tables\Actions\RestoreBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dipulihkan (Bulk)', 'Verifikasi dokumen telah dipulihkan dalam bulk action.', null, null, $record->ajukanDokumenSopMutu->nama_asli_dokumen ?? null);
                            }
                        }),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dihapus Permanen (Bulk)', 'Verifikasi dokumen telah dihapus secara permanen.', null, null, $record->ajukanDokumenSopMutu->nama_asli_dokumen ?? null);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerifikasiDokumenSopMutus::route('/'),
            'create' => Pages\CreateVerifikasiDokumenSopMutu::route('/create'),
            'edit' => Pages\EditVerifikasiDokumenSopMutu::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'ajukanDokumenSopMutu.user',
                'ajukanDokumenSopMutu.substansi',
                'ajukanDokumenSopMutu.subKategoriSop',
                'ajukanDokumenSopMutu.statusProgress',
                'user',
                'statusProgress'
            ]);
    }
}
