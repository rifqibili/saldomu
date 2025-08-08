<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SertifikatResource\Pages;
use App\Models\Sertifikat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification; // Pastikan ini diimpor
use Filament\Forms\Components\Section; // Tambahkan ini untuk grouping form
use Filament\Tables\Columns\IconColumn; // Untuk menampilkan ikon status

class SertifikatResource extends Resource
{
    protected static ?string $model = Sertifikat::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Dokumen Lain';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralModelLabel = 'Sertifikat'; // Label yang lebih baik di navigasi

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detail Dokumen Sertifikat') // Menggunakan Section untuk mengelompokkan input
                    ->description('Isi informasi terkait dokumen sertifikat.')
                    ->schema([
                        FileUpload::make('dokumen')
                            ->label('Dokumen Sertifikat') // Label disesuaikan
                            ->disk('public') // Simpan file di disk "public"
                            ->directory('sertifikat') // Simpan di folder "sertifikat"
                            ->acceptedFileTypes(['application/pdf']) // Hanya menerima file PDF
                            ->required()
                            ->columnSpanFull() // Membuat field ini mengambil seluruh lebar kolom
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $set) {
                                $originalName = $file->getClientOriginalName();
                                $set('nama_asli_dokumen', $originalName);
                                return $originalName;
                            })
                            ->helperText('Unggah file PDF sertifikat Anda di sini.'), // Teks bantuan
                        Forms\Components\Hidden::make('nama_asli_dokumen'),
                        TextInput::make('tahun')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(2100)
                            ->required()
                            ->placeholder('Contoh: 2023') // Placeholder untuk panduan input
                            ->helperText('Masukkan tahun sertifikat diterbitkan.'), // Teks bantuan
                    ])->columns(2), // Mengatur layout kolom di dalam section
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_asli_dokumen')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false), // Selalu tampil secara default
                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->label('Tanggal Unggah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        Carbon::setLocale('id');
                        return Carbon::parse($state)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y H:i:s');
                    }),
                // Menambahkan kolom untuk status soft delete
                IconColumn::make('deleted_at')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(fn (Sertifikat $record): bool => $record->trashed())
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (Sertifikat $record): string => $record->trashed() ? 'Dihapus Sementara' : 'Aktif')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Tampilkan Data Dihapus?'), // Label filter yang lebih jelas
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('download')
                        ->label('Download File')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Sertifikat $record) {
                            if ($record->dokumen && Storage::disk('public')->exists($record->dokumen)) {
                                return response()->download(
                                    Storage::disk('public')->path($record->dokumen),
                                    $record->nama_asli_dokumen
                                );
                            }
                            Notification::make()
                                ->title('Gagal mengunduh file.')
                                ->body('File dokumen tidak ditemukan atau tidak valid.')
                                ->danger()
                                ->send();
                            return null;
                        }),
                    Action::make('lihat')
                        ->label('Lihat File')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Sertifikat $record) => $record->dokumen && Storage::disk('public')->exists($record->dokumen) ? Storage::url($record->dokumen) : '#')
                        ->openUrlInNewTab()
                        ->visible(fn (Sertifikat $record): bool => (bool)$record->dokumen && Storage::disk('public')->exists($record->dokumen)), // Tampilkan hanya jika ada file dan file ada di storage
                    DeleteAction::make() // Soft delete
                        ->after(function (Sertifikat $record) {
                            $record->recordHistory('Dihapus', 'Sertifikat telah dihapus secara soft-delete.', null, null, $record->nama_asli_dokumen);
                        }),
                    RestoreAction::make() // Restore dari soft delete
                        ->after(function (Sertifikat $record) {
                            $record->recordHistory('Dipulihkan', 'Sertifikat telah dipulihkan dari soft-delete.', null, null, $record->nama_asli_dokumen);
                        }),
                    ForceDeleteAction::make() // Hapus permanen
                        ->after(function (Sertifikat $record) {
                            // Hapus file dari storage saat dihapus permanen
                            if ($record->dokumen && Storage::disk('public')->exists($record->dokumen)) {
                                Storage::disk('public')->delete($record->dokumen);
                            }
                            $record->recordHistory('Dihapus Permanen', 'Sertifikat telah dihapus secara permanen.', null, null, $record->nama_asli_dokumen);
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dihapus (Bulk)', 'Sertifikat telah dihapus secara soft-delete dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    Tables\Actions\RestoreBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dipulihkan (Bulk)', 'Sertifikat telah dipulihkan dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                // Hapus file dari storage saat dihapus permanen
                                if ($record->dokumen && Storage::disk('public')->exists($record->dokumen)) {
                                    Storage::disk('public')->delete($record->dokumen);
                                }
                                $record->recordHistory('Dihapus Permanen (Bulk)', 'Sertifikat telah dihapus permanen dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make('sertifikat-excel') // Nama export yang lebih spesifik
                            ->fromTable() // Pastikan mengambil data dari tabel
                            ->withColumns([
                                // Definisikan kolom yang ingin diekspor
                                \Maatwebsite\Excel\Concerns\WithMapping::class => function ($record) {
                                    return [
                                        $record->nama_asli_dokumen,
                                        $record->tahun,
                                        Carbon::parse($record->created_at)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y H:i:s'),
                                    ];
                                },
                                \Maatwebsite\Excel\Concerns\WithHeadings::class => function () {
                                    return ['Nama Dokumen', 'Tahun', 'Tanggal Unggah'];
                                },
                            ]),
                    ]),
                    Tables\Actions\BulkAction::make('copy_data') // Nama aksi yang lebih jelas
                        ->label('Salin Data ke Clipboard') // Label yang lebih informatif
                        ->icon('heroicon-o-clipboard')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $data = '';
                            $headers = ['Nama Dokumen', 'Tahun', 'Tanggal Unggah'];
                            $data .= implode("\t", $headers) . "\n";

                            foreach ($records as $record) {
                                $row = [
                                    $record->nama_asli_dokumen,
                                    $record->tahun,
                                    Carbon::parse($record->created_at)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y H:i:s'),
                                ];
                                $data .= implode("\t", $row) . "\n";
                            }

                            // Menggunakan notifikasi untuk menampilkan data yang bisa disalin
                            Notification::make()
                                ->title('Data Sertifikat Siap Disalin')
                                ->body('Silakan salin teks di bawah ini ke clipboard Anda:<br><textarea class="w-full h-32 p-2 border rounded-md font-mono text-sm" readonly onfocus="this.select()">' . htmlspecialchars($data) . '</textarea>')
                                ->success()
                                ->persistent() // Notifikasi akan tetap ada sampai ditutup manual
                                ->duration(null) // Tanpa durasi otomatis
                                ->send();
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
            'index' => Pages\ListSertifikats::route('/'),
            'create' => Pages\CreateSertifikat::route('/create'),
            'edit' => Pages\EditSertifikat::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
