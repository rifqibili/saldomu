<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AjukanDokumenSopMutuResource\Pages;
use App\Models\AjukanDokumenSopMutu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Models\StatusProgress;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Substansi;
use App\Models\SubKategoriSop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AjukanDokumenSopMutuResource extends Resource
{
    protected static ?string $model = AjukanDokumenSopMutu::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $navigationLabel = 'Ajukan Dokumen SOP Mutu';
    protected static ?string $navigationGroup = 'Manajemen Dokumen';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $substansiOptions = Substansi::pluck('substansi', 'id')->toArray();
        $subKategoriSopOptions = SubKategoriSop::pluck('jenis_sop', 'id')->toArray();

        $sopMakroId = SubKategoriSop::where('jenis_sop', 'SOP Makro')->value('id');
        $sopMikroId = SubKategoriSop::where('jenis_sop', 'SOP Mikro')->value('id');

        $subKategoriSopColors = [];
        if ($sopMakroId) {
            $subKategoriSopColors[$sopMakroId] = 'info';
        }
        if ($sopMikroId) {
            $subKategoriSopColors[$sopMikroId] = 'success';
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengajuan')
                    ->description('Isi detail dokumen yang akan diajukan.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Pengaju')
                                    ->relationship('user', 'nama')
                                    ->default(auth()->user()->id)
                                    ->disabled()
                                    ->required(),
                                Hidden::make('user_id')
                                    ->dehydrateStateUsing(fn ($state) => auth()->id()),

                                Radio::make('substansi_id')
                                    ->label('Substansi')
                                    ->options($substansiOptions)
                                    ->inline()
                                    ->required()
                                    ->extraAttributes([
                                        'class' => 'flex flex-wrap gap-2'
                                    ]),

                                Forms\Components\ToggleButtons::make('sub_kategori_sop_id')
                                    ->label('Jenis SOP')
                                    ->options($subKategoriSopOptions)
                                    ->inline()
                                    ->required()
                                    ->colors($subKategoriSopColors)
                                    ->grouped()
                                    ->columnSpanFull(),
                            ]),

                        // DateTimePicker::make('waktu_pengajuan')
                        //     ->label('Waktu Pengajuan')
                        //     ->default(now())
                        //     ->required()
                        //     ->seconds(false)
                        //     ->withoutSeconds()
                        //     ->columnSpanFull(),
                        FileUpload::make('nama_dokumen')
                            ->required()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                    $set('nama_asli_dokumen', $state->getClientOriginalName());
                                }
                            })
                            ->rules([
                                function (callable $get) {
                                    return function (string $attribute, $value, callable $fail) use ($get) {
                                        $namaAsliDokumen = $value->getClientOriginalName();
                                        $statusDiterimaId = 1; // Assuming 'Diterima' has ID 1

                                        $existingAcceptedDocument = AjukanDokumenSopMutu::where('nama_asli_dokumen', $namaAsliDokumen)
                                            ->where('status_progress_id', $statusDiterimaId)
                                            ->exists();

                                        if ($existingAcceptedDocument) {
                                            $fail("File dengan nama '{$namaAsliDokumen}' sudah pernah diajukan dan telah Diterima. Silakan ajukan dokumen baru.");
                                        }
                                    };
                                },
                            ]),
                        Hidden::make('nama_asli_dokumen'),
                        Hidden::make('status_progress_id'),
                        Forms\Components\Placeholder::make('keterangan_verifikator')
                            ->label('Keterangan dari Verifikator')
                            ->content(function (?Model $record) {
                                if (!$record) return null;

                                $latestVerification = $record->verifikasiDokumenSopMutus()->latest('created_at')->first();
                                return $latestVerification ? $latestVerification->keterangan : 'Belum ada keterangan';
                            })
                            ->visible(function (string $operation) {
                                return $operation === 'edit';
                            })
                            ->columnSpanFull()
                            ->extraAttributes([
                                'class' => 'p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 border border-yellow-300 dark:border-yellow-700',
                            ]),
                    ])
                    ->columns(1),
                    
                Forms\Components\Placeholder::make('notes_for_pengaju')
                    ->label('Catatan Penting untuk Pengaju')
                    ->content('Mohon lebih teliti dalam memasukkan nama file Anda agar konsisten di setiap pengajuan. Nama file yang konsisten akan mempermudah proses verifikasi dan penerbitan dokumen Anda di masa mendatang.')
                    ->visible(function (string $operation, ?Model $record) {
                        if ($operation !== 'edit' || !$record) {
                            return false;
                        }
                        $currentStatus = $record->statusProgress->status ?? null;
                        Log::info('AjukanDokumenSopMutu ID: ' . $record->id . ' - Current Status: ' . ($currentStatus ?? 'NULL'));
                        return $currentStatus !== 'Diterima';
                    })
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'p-4 rounded-lg bg-blue-50 dark:bg-blue-900 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-700',
                    ]),
            ]);
            
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')->label('Pengaju')->searchable()->sortable(),
                TextColumn::make('substansi.substansi')->label('Substansi')->searchable()->sortable(),
                TextColumn::make('subKategoriSop.jenis_sop')->label('Jenis SOP')->searchable()->sortable(),
                TextColumn::make('nama_asli_dokumen')
                    ->label('Nama Asli Dokumen')
                    ->searchable(),
                TextColumn::make('verifikator')
                    ->label('Verifikator')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $latestVerification = $record->verifikasiDokumenSopMutus()->latest('created_at')->first();
                        return $latestVerification?->user?->nama ?? 'Belum ada';
                    }),
                TextColumn::make('nomor_revisi')->label('No. Revisi')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => sprintf('%02d', $state)),
                TextColumn::make('waktu_pengajuan')
                    ->label('Waktu Pengajuan')
                    ->formatStateUsing(function ($state) {
                        Carbon::setLocale('id');
                        return Carbon::parse($state)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y, H:i');
                    }),
                BadgeColumn::make('prioritas_pengajuan')
                    ->label('Prioritas')
                    ->colors([
                        'success' => 'rendah',
                        'info' => 'normal',
                        'warning' => 'tinggi',
                        'danger' => 'urgent',
                    ]),
                BadgeColumn::make('statusProgress.status')
                    ->label('Status Progress')
                    ->badge()
                    ->colors([
                        'success' => 'Diterima',
                        'danger' => 'Ditolak',
                        'info' => 'Review',
                        'warning' => fn ($state): bool => in_array($state, ['Revisi', 'Menunggu di Review']),
                    ]),
                TextColumn::make('verifikasiDokumenSopMutus.keterangan')
                    ->label('Keterangan')
                    ->getStateUsing(function ($record) {
                        $latestVerification = $record->verifikasiDokumenSopMutus()->latest('created_at')->first();
                        return $latestVerification ? $latestVerification->keterangan : '';
                    })
                    ->limit(50),
                TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_progress_id')
                    ->label('Status Progress')
                    ->relationship('statusProgress', 'status'),
                SelectFilter::make('substansi_id')
                    ->label('Substansi')
                    ->relationship('substansi', 'substansi'),
                SelectFilter::make('sub_kategori_sop_id')
                    ->label('Jenis SOP')
                    ->relationship('subKategoriSop', 'jenis_sop'),
                SelectFilter::make('user_id')
                    ->label('Pengaju')
                    ->relationship('user', 'nama'),
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->after(function (AjukanDokumenSopMutu $record) {
                            $record->recordHistory('Dihapus', 'Dokumen pengajuan telah dihapus secara soft-delete.', null, null, $record->nama_asli_dokumen);
                        })
                        ->visible(function (AjukanDokumenSopMutu $record) {
                            $statusIdMenungguRevisi = StatusProgress::where('status', 'Menunggu di Review')->value('id');
                            return $record->status_progress_id === $statusIdMenungguRevisi;
                        }),
                    RestoreAction::make()
                        ->after(function (AjukanDokumenSopMutu $record) {
                            $record->recordHistory('Dipulihkan', 'Dokumen pengajuan telah dipulihkan dari soft-delete.', null, null, $record->nama_asli_dokumen);
                        }),
                    ForceDeleteAction::make()
                        ->after(function (AjukanDokumenSopMutu $record) {
                            $record->recordHistory('Dihapus Permanen', 'Dokumen pengajuan telah dihapus secara permanen.', null, null, $record->nama_asli_dokumen);
                        }),
                    Action::make('download')
                        ->label('Download File')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($record) {
                            $filePath = $record->nama_dokumen;
                            $fileName = $record->nama_asli_dokumen;

                            if ($filePath && $fileName) {
                                return response()->download(
                                    Storage::path($filePath),
                                    $fileName
                                );
                            }
                        }),
                    Action::make('lihat')
                        ->label('Lihat File')
                        ->icon('heroicon-o-eye')
                        ->url(fn ($record) => url(Storage::url($record->nama_dokumen)))
                        ->openUrlInNewTab(),
                    Action::make('view_history')
                        ->label('Lihat Riwayat')
                        ->icon('heroicon-o-clock')
                        ->url(fn (AjukanDokumenSopMutu $record): string =>
                            route('filament.admin.resources.document-histories.index', [
                                'documentable_id' => $record->id,
                                'documentable_type' => get_class($record),
                            ])
                        )
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dihapus (Bulk)', 'Dokumen pengajuan telah dihapus secara soft-delete dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    Tables\Actions\RestoreBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dipulihkan (Bulk)', 'Dokumen pengajuan telah dipulihkan dalam bulk action.', null, null, $record->nama_asli_dokumen);
                            }
                        }),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->recordHistory('Dihapus Permanen (Bulk)', 'Dokumen pengajuan telah dihapus secara permanen.', null, null, $record->nama_asli_dokumen);
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
            'index' => Pages\ListAjukanDokumenSopMutus::route('/'),
            'create' => Pages\CreateAjukanDokumenSopMutu::route('/create'),
            'edit' => Pages\EditAjukanDokumenSopMutu::route('/{record}/edit'),
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
