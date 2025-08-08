<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DokumenSopMutuResource\Pages;
use App\Models\DokumenSopMutu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport; 
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\RestoreAction; // <-- Tambahkan ini
use Filament\Tables\Actions\ForceDeleteAction; // <-- Tambahkan ini
use Carbon\Carbon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter; // <-- Tambahkan ini
use Illuminate\Database\Eloquent\Builder; // <-- Pastikan ini ada
use Illuminate\Database\Eloquent\SoftDeletingScope; // <-- Tambahkan ini

class DokumenSopMutuResource extends Resource
{
    protected static ?string $model = DokumenSopMutu::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'SOP Mutu';
    protected static ?string $navigationLabel = 'SOP Mutu';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nomor_sop')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('nama_sop')
                    ->required()
                    ->maxLength(255),
                Select::make('substansi_id')
                    ->relationship('substansi', 'substansi')
                    ->required(),
                DateTimePicker::make('tanggal_terbit')
                    ->required(),
                TextInput::make('nomor_revisi')
                    ->numeric()
                    ->default(0),
                Select::make('status_id')
                    ->relationship('status', 'status')
                    ->required(),
                Select::make('sub_kategori_sop_id')
                    ->relationship('subKategoriSop', 'jenis_sop')
                    ->required(),
                FileUpload::make('file_path')
                    ->label('Unggah Dokumen')
                    ->disk('public')
                    ->directory('dokumen-sop-mutu')
                    ->acceptedFileTypes(['application/pdf'])
                    ->required()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state instanceof TemporaryUploadedFile) {
                            $fileName = $state->getClientOriginalName();
                            $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
                            $set('nama_sop', $fileNameWithoutExtension);
                            $set('nama_asli_dokumen', $fileName);
                        }
                    }),
                Hidden::make('nama_asli_dokumen'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_sop')->searchable()->sortable(),
                TextColumn::make('nama_sop')->searchable(),
                TextColumn::make('substansi.substansi')->label('Substansi')->sortable(),
                TextColumn::make('status.status')->label('Status')->sortable()->badge()
                    ->colors([
                        'success' => 'Aktif',
                        'danger' => 'Non-aktif',
                    ]),
                TextColumn::make('subKategoriSop.jenis_sop')->label('Sub Kategori')->sortable(),
                TextColumn::make('nomor_revisi')
                    ->label('No. Revisi')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => sprintf('%02d', $state)),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        Carbon::setLocale('id');
                        return Carbon::parse($state)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y H:i:s');
                    }),
                TextColumn::make('deleted_at') // <-- Tambahkan kolom deleted_at
                    ->label('Dihapus Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_id')
                    ->label('Status Dokumen')
                    ->relationship('status', 'status'),
                SelectFilter::make('substansi_id')
                    ->label('Substansi')
                    ->relationship('substansi', 'substansi'),
                SelectFilter::make('sub_kategori_sop_id')
                    ->label('Jenis SOP')
                    ->relationship('subKategoriSop', 'jenis_sop'),
                TrashedFilter::make(), // <-- Tambahkan filter ini
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    // Modifikasi DeleteAction untuk mencatat riwayat
                    DeleteAction::make()
                        ->after(function (DokumenSopMutu $record) {
                            $record->recordHistory('Dihapus', 'Dokumen SOP Mutu telah dihapus secara soft-delete.');
                        }),
                    RestoreAction::make() // <-- Tambahkan aksi Restore
                        ->after(function (DokumenSopMutu $record) {
                            $record->recordHistory('Dipulihkan', 'Dokumen SOP Mutu telah dipulihkan dari soft-delete.');
                        }),
                    ForceDeleteAction::make() // <-- Tambahkan aksi Force Delete
                        ->after(function (DokumenSopMutu $record) {
                            $record->recordHistory('Dihapus Permanen', 'Dokumen SOP Mutu telah dihapus secara permanen.');
                        }),
                    Action::make('download')
                        ->label('Download File')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($record) {
                            if ($record->file_path && $record->nama_asli_dokumen) {
                                $filePath = $record->file_path;
                                $fileName = $record->nama_asli_dokumen;
                            } elseif ($record->ajukanDokumenSopMutu) {
                                $filePath = $record->ajukanDokumenSopMutu->nama_dokumen;
                                $fileName = $record->ajukanDokumenSopMutu->nama_asli_dokumen;
                            } else {
                                Notification::make()
                                    ->title('Gagal mengunduh file.')
                                    ->body('Dokumen tidak ditemukan.')
                                    ->danger()
                                    ->send();
                                return null;
                            }

                            if ($filePath) {
                                return response()->download(
                                    Storage::disk('public')->path($filePath),
                                    $fileName
                                );
                            }
                            
                            return null;
                        }),
                    Action::make('lihat')
                        ->label('Lihat File')
                        ->icon('heroicon-o-eye')
                        ->url(function ($record) {
                            $filePath = $record->file_path ?? optional($record->ajukanDokumenSopMutu)->nama_dokumen;
                            return $filePath ? Storage::url($filePath) : '#';
                        })
                        ->openUrlInNewTab(),
                    Action::make('view_history')
                        ->label('Lihat Riwayat')
                        ->icon('heroicon-o-clock')
                        ->url(fn (DokumenSopMutu $record): string =>
                            route('filament.admin.resources.document-histories.index', [
                                'documentable_id' => $record->id,
                                'documentable_type' => get_class($record),
                            ])
                        )
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dihapus (Bulk)', 'Dokumen SOP Mutu telah dihapus secara soft-delete dalam bulk action.');
                            }
                        }),
                    Tables\Actions\RestoreBulkAction::make() // <-- Tambahkan bulk restore
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dipulihkan (Bulk)', 'Dokumen SOP Mutu telah dipulihkan dalam bulk action.');
                            }
                        }),
                    Tables\Actions\ForceDeleteBulkAction::make() // <-- Tambahkan bulk force delete
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dihapus Permanen (Bulk)', 'Dokumen SOP Mutu telah dihapus permanen dalam bulk action.');
                            }
                        }),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make('dokumen-sop-mutu-excel'),
                    ]),
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
            'index' => Pages\ListDokumenSopMutus::route('/'),
            'create' => Pages\CreateDokumenSopMutu::route('/create'),
            'edit' => Pages\EditDokumenSopMutu::route('/{record}/edit'),
        ];
    }

    // Pastikan query dasar tidak mengabaikan soft deletes secara default,
    // TrashedFilter akan menanganinya.
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class, // Pastikan ini tidak menghapus scope soft delete
            ]);
    }
}
