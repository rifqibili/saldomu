<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentHistoryResource\Pages;
use App\Models\DocumentHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Filament\Tables\Filters;
use Illuminate\Support\Facades\Auth; // <-- Tambahkan ini
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;

class DocumentHistoryResource extends Resource
{
    protected static ?string $model = DocumentHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Manajemen Dokumen';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Riwayat Dokumen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('documentable_type')
                    ->label('Jenis Dokumen')
                    ->options([
                        'App\\Models\\AjukanDokumenSopMutu' => 'Pengajuan Dokumen',
                        'App\\Models\\DokumenSopMutu' => 'SOP Mutu Aktif',
                    ])
                    ->disabled(),
                Forms\Components\TextInput::make('documentable_id')
                    ->label('ID Dokumen')
                    ->disabled(),
                Forms\Components\Select::make('user_id')
                    ->label('Dilakukan Oleh')
                    ->relationship('user', 'nama')
                    ->disabled(),
                Forms\Components\TextInput::make('action')
                    ->label('Aksi')
                    ->disabled(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->disabled(),
                Forms\Components\TextInput::make('old_status')
                    ->label('Status Lama')
                    ->disabled(),
                Forms\Components\TextInput::make('new_status')
                    ->label('Status Baru')
                    ->disabled(),
                Forms\Components\TextInput::make('nama_asli_dokumen') // <-- Tambahkan ini di form jika ingin terlihat
                    ->label('Nama Asli Dokumen')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->defaultGroup('nama_asli_dokumen') // <-- Tambahkan ini untuk mengelompokkan berdasarkan nama_asli_dokumen;
            ->columns([
                // Menggunakan kolom nama_asli_dokumen yang baru disimpan
                TextColumn::make('nama_asli_dokumen')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Waktu & Tanggal')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        Carbon::setLocale('id');
                        return Carbon::parse($state)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y, H:i:s');
                    }),
                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->colors([
                        'primary' => 'Diajukan',
                        'info' => 'Direview',
                        'success' => 'Diterima',
                        'danger' => 'Ditolak',
                        'warning' => 'Direvisi',
                        'success' => 'Diterbitkan',
                        'gray' => 'Dihapus',
                        'info' => 'Dipulihkan', // Untuk aksi restore
                        'danger' => 'Dihapus Permanen', // Untuk aksi force delete
                    ])
                    ->sortable(),
                TextColumn::make('user.nama')
                    ->label('Dilakukan Oleh')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('old_status')
                    ->label('Status Lama')
                    ->badge()
                    ->colors([
                        'warning' => 'Menunggu di Review',
                        'info' => 'Review',
                        'success' => 'Diterima',
                        'danger' => 'Ditolak',
                        'warning' => 'Revisi',
                    ]),
                TextColumn::make('new_status')
                    ->label('Status Baru')
                    ->badge()
                    ->colors([
                        'warning' => 'Menunggu di Review',
                        'info' => 'Review',
                        'success' => 'Diterima',
                        'danger' => 'Ditolak',
                        'warning' => 'Revisi',
                        'primary' => 'Aktif',
                    ]),
                TextColumn::make('documentable_type')
                    ->label('Jenis Dokumen')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\\Models\\AjukanDokumenSopMutu' => 'Pengajuan',
                        'App\\Models\\DokumenSopMutu' => 'SOP Aktif',
                        'App\\Models\\Sertifikat' => 'Sertifikat',
                        'App\\Models\\SopMakro' => 'SOP Makro',
                        'App\\Models\\LaporanAudit' => 'Laporan Audit',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(70)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState()),
                // Kolom documentable_type dan documentable_id bisa tetap ada atau dihilangkan
                // jika nama_asli_dokumen sudah cukup sebagai identifikasi utama
                // TextColumn::make('documentable_type')
                //     ->label('Jenis Dokumen')
                //     ->formatStateUsing(fn (string $state): string => match ($state) {
                //         'App\\Models\\AjukanDokumenSopMutu' => 'Pengajuan',
                //         'App\\Models\\DokumenSopMutu' => 'SOP Aktif',
                //         default => $state,
                //     })
                //     ->sortable(),
                //     // ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan secara default
                TextColumn::make('documentable_id')
                    ->label('ID Dokumen')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan secara default
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Filter Aksi')
                    ->options([
                        'Diajukan' => 'Diajukan',
                        'Direview' => 'Direview',
                        'Diterima' => 'Diterima',
                        'Ditolak' => 'Ditolak',
                        'Direvisi' => 'Direvisi',
                        'Diterbitkan' => 'Diterbitkan',
                        'Dihapus' => 'Dihapus',
                        'Dipulihkan' => 'Dipulihkan',
                        'Dihapus Permanen' => 'Dihapus Permanen',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Filter Pengguna')
                    ->relationship('user', 'nama'),
                Tables\Filters\SelectFilter::make('documentable_type')
                    ->label('Filter Jenis Dokumen')
                    ->options([
                        'App\\Models\\AjukanDokumenSopMutu' => 'Pengajuan',
                        'App\\Models\\DokumenSopMutu' => 'SOP Aktif',
                        'App\\Models\\Sertifikat' => 'Sertifikat',
                        'App\\Models\\SopMakro' => 'SOP Makro',
                        'App\\Models\\LaporanAudit' => 'Laporan Audit',
                    ]),
                // Tables\Filters\TextInputFilter::make('nama_asli_dokumen') // <-- Tambahkan filter berdasarkan nama asli dokumen
                //     ->label('Cari Nama Dokumen')
                //     ->query(function (Builder $query, array $data): Builder {
                //         return $query
                //             ->when(
                //                 $data['value'],
                //                 fn (Builder $query, $value): Builder => $query->where('nama_asli_dokumen', 'like', "%{$value}%")
                //             );
                //     }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListDocumentHistories::route('/'),
            'view' => Pages\ViewDocumentHistory::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $documentableId = request()->query('documentable_id');
        $documentableType = request()->query('documentable_type');

        if ($documentableId && $documentableType) {
            // Jika ada filter spesifik dari URL, filter berdasarkan itu
            $query->where('documentable_id', $documentableId)
                  ->where('documentable_type', $documentableType);
            // Untuk tampilan spesifik dokumen, urutkan berdasarkan waktu terbaru secara default
            $query->orderByDesc('created_at');
        } else {
            // Untuk tampilan global, urutkan berdasarkan nama_asli_dokumen untuk pengelompokan visual
            // Kemudian berdasarkan tipe dokumen. Jangan tambahkan orderBy('created_at') di sini
            // agar sortable() pada kolom created_at di table() bisa berfungsi.
            $query->orderBy('nama_asli_dokumen')
                  ->orderBy('documentable_type');
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
      public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin']); 
    }
}
