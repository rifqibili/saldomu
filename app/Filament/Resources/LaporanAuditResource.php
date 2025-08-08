<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanAuditResource\Pages;
use App\Models\LaporanAudit;
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
use pxlrbt\FilamentExcel\Exports\PdfExport;
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
use Filament\Tables\Columns\IconColumn; // Untuk menampilkan ikon status jika diperlukan

class LaporanAuditResource extends Resource
{
    protected static ?string $model = LaporanAudit::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Dokumen Lain';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralModelLabel = 'Laporan Audit'; // Label yang lebih baik di navigasi

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detail Laporan Audit') // Menggunakan Section untuk mengelompokkan input
                    ->description('Isi informasi terkait dokumen laporan audit.')
                    ->schema([
                        FileUpload::make('dokumen')
                            ->label('Dokumen Laporan Audit')
                            ->disk('public') // Simpan file di disk "public"
                            ->directory('laporan-audit') // Simpan di folder "laporan-audit"
                            ->acceptedFileTypes(['application/pdf']) // Hanya menerima file PDF
                            ->required()
                            ->columnSpanFull() // Membuat field ini mengambil seluruh lebar kolom
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $set) {
                                $originalName = $file->getClientOriginalName();
                                $set('nama_asli_dokumen', $originalName);
                                return $originalName;
                            })
                            ->helperText('Unggah file PDF laporan audit Anda di sini.'), // Teks bantuan
                        Forms\Components\Hidden::make('nama_asli_dokumen'),
                        TextInput::make('tahun')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(2100)
                            ->required()
                            ->placeholder('Contoh: 2023') // Placeholder untuk panduan input
                            ->helperText('Masukkan tahun laporan audit diterbitkan.'), // Teks bantuan
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
                // Jika ingin menambahkan kolom untuk status soft delete (opsional)
                IconColumn::make('deleted_at')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(fn (LaporanAudit $record): bool => $record->trashed())
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (LaporanAudit $record): string => $record->trashed() ? 'Dihapus Sementara' : 'Aktif')
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
                        ->action(function (LaporanAudit $record) {
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
                        ->url(fn (LaporanAudit $record) => $record->dokumen && Storage::disk('public')->exists($record->dokumen) ? Storage::url($record->dokumen) : '#')
                        ->openUrlInNewTab()
                        ->visible(fn (LaporanAudit $record): bool => (bool)$record->dokumen && Storage::disk('public')->exists($record->dokumen)), // Tampilkan hanya jika ada file dan file ada di storage
                    DeleteAction::make() // Soft delete
                        ->after(function (LaporanAudit $record) {
                            $record->recordHistory('Dihapus', 'Laporan audit telah dihapus secara soft-delete.', null, null, $record->nama_asli_dokumen);
                        }),
                    RestoreAction::make() // Restore dari soft delete
                        ->after(function (LaporanAudit $record) {
                            $record->recordHistory('Dipulihkan', 'Laporan audit telah dipulihkan dari soft-delete.', null, null, $record->nama_asli_dokumen);
                        }),
                    ForceDeleteAction::make() // Hapus permanen
                        ->after(function (LaporanAudit $record) {
                            // Hapus file dari storage saat dihapus permanen
                            if ($record->dokumen && Storage::disk('public')->exists($record->dokumen)) {
                                Storage::disk('public')->delete($record->dokumen);
                            }
                            $record->recordHistory('Dihapus Permanen', 'Laporan audit telah dihapus secara permanen.', null, null, $record->nama_asli_dokumen);
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dihapus (Bulk)', 'Laporan audit telah dihapus secara soft-delete dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    Tables\Actions\RestoreBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dipulihkan (Bulk)', 'Laporan audit telah dipulihkan dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                // Hapus file dari storage saat dihapus permanen
                                if ($record->dokumen && Storage::disk('public')->exists($record->dokumen)) {
                                    Storage::disk('public')->delete($record->dokumen);
                                }
                                $record->recordHistory('Dihapus Permanen (Bulk)', 'Laporan audit telah dihapus permanen dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make('laporan-audit-excel') // Nama export yang lebih spesifik
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
                                ->title('Data Laporan Audit Siap Disalin')
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
            'index' => Pages\ListLaporanAudits::route('/'),
            'create' => Pages\CreateLaporanAudit::route('/create'),
            'edit' => Pages\EditLaporanAudit::route('/{record}/edit'),
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
