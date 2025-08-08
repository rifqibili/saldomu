<?php

namespace App\Filament\Resources\AjukanDokumenSopMutuResource\Pages;

use App\Filament\Resources\AjukanDokumenSopMutuResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\VerifikasiDokumenSopMutu;
use App\Models\AjukanDokumenSopMutu;
use App\Models\User; 
use App\Models\StatusProgress;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Exception;

class CreateAjukanDokumenSopMutu extends CreateRecord
{
    protected static string $resource = AjukanDokumenSopMutuResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $namaAsliDokumen = $data['nama_asli_dokumen'];

        $existingDocument = AjukanDokumenSopMutu::where('nama_asli_dokumen', $namaAsliDokumen)
                                               ->orderBy('nomor_revisi', 'desc')
                                               ->first();

        if ($existingDocument) {
            $data['nomor_revisi'] = $existingDocument->nomor_revisi + 1;
        } else {
            $data['nomor_revisi'] = 0;
        }

        $data['status_progress_id'] = StatusProgress::where('status', 'Menunggu di Review')->value('id'); 
        if (is_null($data['status_progress_id'])) {
            $data['status_progress_id'] = 4; 
        }

        return $data;
    }
        
    protected function afterCreate(): void
    {
        $record = $this->record; // Ini adalah record AjukanDokumenSopMutu yang baru dibuat
        
        $statusMenungguDiReviewId = StatusProgress::where('status', 'Menunggu di Review')->value('id');

        try {
            // Buat record baru di VerifikasiDokumenSopMutu
            VerifikasiDokumenSopMutu::create([
                'ajukan_dokumen_sop_mutu_id' => $record->id,
                'user_id' => auth()->id(), 
                'status_progress_id' => $statusMenungguDiReviewId,
                'keterangan' => 'Dokumen baru diajukan, menunggu review.',
            ]);
            \Log::info('VerifikasiDokumenSopMutu record created after AjukanDokumenSopMutu creation.');

            // --- CATAT RIWAYAT: Dokumen Diajukan ---
            $record->recordHistory(
                'Diajukan', 
                'Dokumen baru diajukan untuk verifikasi.',
                null, // Tidak ada status lama
                $record->statusProgress->status, // Status baru
                $record->nama_asli_dokumen // <-- Teruskan nama asli dokumen
            );
            \Log::info('Document history recorded: Diajukan for AjukanDokumenSopMutu ID: ' . $record->id);


            // --- Logika Notifikasi ---
            $status = $record->statusProgress->status;
            $pengaju = $record->user;
            $namaDokumen = $record->nama_asli_dokumen;

            // URL untuk melihat file PDF langsung (untuk verifikator)
            $fileUrl = Storage::url($record->nama_dokumen); 
            // URL untuk melihat halaman pengajuan dokumen (untuk pengaju)
            $pengajuanUrl = route('filament.admin.resources.ajukan-dokumen-sop-mutus.edit', ['record' => $record->id]);


            if ($status === 'Menunggu di Review') {
                // Notifikasi untuk PENGGUNA (pengaju dokumen)
                Notification::make()
                    ->title('Dokumen Berhasil Diajukan')
                    ->body("Dokumen '{$namaDokumen}' telah berhasil diajukan dan sedang menunggu review.")
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('secondary')
                    ->actions([
                        Action::make('view_submission')
                            ->label('Lihat Pengajuan Saya')
                            ->url($pengajuanUrl, shouldOpenInNewTab: true)
                            ->icon('heroicon-o-eye'),
                    ])
                    ->sendToDatabase($pengaju);
                \Log::info('Notification sent to Pengaju after submission.');

                // Notifikasi untuk VERIFIKATOR (peran 'mr')
                $verifikators = User::role('mr')->get(); 
                
                foreach ($verifikators as $verifikator) {
                    Notification::make()
                        ->title('Pengajuan Dokumen Baru')
                        ->body("Pengajuan baru untuk dokumen '{$namaDokumen}' telah masuk. Mohon untuk segera di-review.")
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->color('warning')
                        ->actions([
                            Action::make('view_file')
                                ->label('Lihat Dokumen')
                                ->url($fileUrl, shouldOpenInNewTab: true) 
                                ->icon('heroicon-o-eye'),
                            Action::make('review_submission')
                                ->label('Review Pengajuan')
                                ->url(route('filament.admin.resources.verifikasi-dokumen-sop-mutus.edit', ['record' => $record->verifikasiDokumenSopMutus->first()->id]), shouldOpenInNewTab: true)
                                ->icon('heroicon-o-pencil-square'),
                        ])
                        ->sendToDatabase($verifikator);
                }
                \Log::info('Notification sent to Verifikators with view file action after submission.');
            }
        } catch (Exception $e) {
            \Log::error('Error in CreateAjukanDokumenSopMutu afterCreate: ' . $e->getMessage());
            Notification::make()
                ->title('Terjadi kesalahan saat pengajuan dokumen.')
                ->body('Silakan periksa log aplikasi untuk detail lebih lanjut.')
                ->danger()
                ->send();
            throw $e; 
        }
    }
}
