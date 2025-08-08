<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SopMakroResource\Pages;
use App\Models\SopMakro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
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
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\ToggleButtons;

class SopMakroResource extends Resource
{
    protected static ?string $model = SopMakro::class;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Dokumen Lain';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralModelLabel = 'SOP Makro';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detail Dokumen SOP Makro')
                    ->description('Isi informasi terkait dokumen SOP Makro.')
                    ->schema([
                        FileUpload::make('dokumen')
                            ->label('Dokumen SOP Makro')
                            ->disk('public')
                            ->directory('sop-makro')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required()
                            ->columnSpanFull()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $set) {
                                $originalName = $file->getClientOriginalName();
                                $set('nama_asli_dokumen', $originalName);
                                return $originalName;
                            })
                            ->helperText('Unggah file PDF SOP Makro Anda di sini.'),
                        ToggleButtons::make('status')
                            ->options([
                                'Aktif' => 'Aktif',
                                'Non-aktif' => 'Non-aktif',
                            ])
                            ->inline() // tampil horizontal
                            ->label('Status'),
                        TextInput::make('tahun')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(2100)
                            ->required()
                            ->placeholder('Contoh: 2023')
                            ->helperText('Masukkan tahun SOP Makro diterbitkan.'),
                    ])->columns(2),
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
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Non-aktif' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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
                IconColumn::make('deleted_at')
                    ->label('Status Hapus')
                    ->boolean()
                    ->getStateUsing(fn (SopMakro $record): bool => $record->trashed())
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (SopMakro $record): string => $record->trashed() ? 'Dihapus Sementara' : 'Aktif')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Tampilkan Data Dihapus?'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('toggle_status') // Aksi baru untuk mengubah status
                        ->label(fn (SopMakro $record): string => $record->status === 'Aktif' ? 'Non-aktifkan' : 'Aktifkan')
                        ->icon(fn (SopMakro $record): string => $record->status === 'Aktif' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn (SopMakro $record): string => $record->status === 'Aktif' ? 'danger' : 'success')
                        ->action(function (SopMakro $record) {
                            $newStatus = ($record->status === 'Aktif') ? 'Non-aktif' : 'Aktif';
                            $record->update(['status' => $newStatus]);

                            Notification::make()
                                ->title('Status berhasil diperbarui!')
                                ->body("Status dokumen {$record->nama_asli_dokumen} diubah menjadi {$newStatus}.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn (SopMakro $record): bool => !$record->trashed()) // Sembunyikan jika record di-soft-delete
                        ->tooltip(fn (SopMakro $record): string => $record->status === 'Aktif' ? 'Klik untuk Non-aktifkan' : 'Klik untuk Aktifkan'),
                    Action::make('download')
                        ->label('Download File')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (SopMakro $record) {
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
                        ->url(fn (SopMakro $record) => $record->dokumen && Storage::disk('public')->exists($record->dokumen) ? Storage::url($record->dokumen) : '#')
                        ->openUrlInNewTab()
                        ->visible(fn (SopMakro $record): bool => (bool)$record->dokumen && Storage::disk('public')->exists($record->dokumen)),
                    DeleteAction::make()
                        ->after(function (SopMakro $record) {
                            $record->recordHistory('Dihapus', 'SOP Makro telah dihapus secara soft-delete.', null, null, $record->nama_asli_dokumen);
                        }),
                    RestoreAction::make()
                        ->after(function (SopMakro $record) {
                            $record->recordHistory('Dipulihkan', 'SOP Makro telah dipulihkan dari soft-delete.', null, null, $record->nama_asli_dokumen);
                        }),
                    ForceDeleteAction::make()
                        ->after(function (SopMakro $record) {
                            if ($record->dokumen && Storage::disk('public')->exists($record->dokumen)) {
                                Storage::disk('public')->delete($record->dokumen);
                            }
                            $record->recordHistory('Dihapus Permanen', 'SOP Makro telah dihapus secara permanen.', null, null, $record->nama_asli_dokumen);
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dihapus (Bulk)', 'SOP Makro telah dihapus secara soft-delete dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    Tables\Actions\RestoreBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dipulihkan (Bulk)', 'SOP Makro telah dipulihkan dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                if ($record->dokumen && Storage::disk('public')->exists($record->dokumen)) {
                                    Storage::disk('public')->delete($record->dokumen);
                                }
                                $record->recordHistory('Dihapus Permanen (Bulk)', 'SOP Makro telah dihapus permanen dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make('sop-makro-excel')
                            ->fromTable()
                            ->withColumns([
                                \Maatwebsite\Excel\Concerns\WithMapping::class => function ($record) {
                                    return [
                                        $record->nama_asli_dokumen,
                                        $record->status,
                                        $record->tahun,
                                        Carbon::parse($record->created_at)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y H:i:s'),
                                    ];
                                },
                                \Maatwebsite\Excel\Concerns\WithHeadings::class => function () {
                                    return ['Nama Dokumen', 'Status', 'Tahun', 'Tanggal Unggah'];
                                },
                            ]),
                    ]),
                    Tables\Actions\BulkAction::make('copy_data')
                        ->label('Salin Data ke Clipboard')
                        ->icon('heroicon-o-clipboard')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $data = '';
                            $headers = ['Nama Dokumen', 'Status', 'Tahun', 'Tanggal Unggah'];
                            $data .= implode("\t", $headers) . "\n";

                            foreach ($records as $record) {
                                $row = [
                                    $record->nama_asli_dokumen,
                                    $record->status,
                                    $record->tahun,
                                    Carbon::parse($record->created_at)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y H:i:s'),
                                ];
                                $data .= implode("\t", $row) . "\n";
                            }

                            Notification::make()
                                ->title('Data SOP Makro Siap Disalin')
                                ->body('Silakan salin teks di bawah ini ke clipboard Anda:<br><textarea class="w-full h-32 p-2 border rounded-md font-mono text-sm" readonly onfocus="this.select()">' . htmlspecialchars($data) . '</textarea>')
                                ->success()
                                ->persistent()
                                ->duration(null)
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
            'index' => Pages\ListSopMakros::route('/'),
            'create' => Pages\CreateSopMakro::route('/create'),
            'edit' => Pages\EditSopMakro::route('/{record}/edit'),
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
