<?php

namespace App\Filament\Resources\VerifikasiDokumenSopMutuResource\Pages;

use App\Filament\Resources\VerifikasiDokumenSopMutuResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action; // <-- PASTIKAN INI DIIMPOR
use Filament\Resources\Pages\EditRecord;
use App\Models\AjukanDokumenSopMutu;
use App\Models\DokumenSopMutu;
use App\Models\StatusProgress;
use App\Models\Status;
use Illuminate\Support\Facades\Storage; // <-- PASTIKAN INI DIIMPOR
use Exception;
use App\Models\Traits\HasDocumentHistory; 
use App\Models\VerifikasiDokumenSopMutu; 


class EditVerifikasiDokumenSopMutu extends EditRecord
{
    protected static string $resource = VerifikasiDokumenSopMutuResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $verifikasiRecord = $this->record;
        $ajukanRecord = AjukanDokumenSopMutu::find($verifikasiRecord->ajukan_dokumen_sop_mutu_id);

        if ($ajukanRecord) {
            $statusMenungguDiReview = StatusProgress::where('status', 'Menunggu di Review')->first();
            $statusReview = StatusProgress::where('status', 'Review')->first();

            if ($statusMenungguDiReview && $statusReview && $ajukanRecord->status_progress_id == $statusMenungguDiReview->id) {
                $ajukanRecord->status_progress_id = $statusReview->id;
                $ajukanRecord->save();
                // CATAT RIWAYAT: Dokumen Direview (saat mount)
                $ajukanRecord->recordHistory(
                    'Direview', 
                    'Dokumen mulai direview oleh verifikator.',
                    $statusMenungguDiReview->status,
                    $statusReview->status,
                    $ajukanRecord->nama_asli_dokumen // Teruskan nama asli dokumen
                );
                \Log::info('Document history recorded: Direview for AjukanDokumenSopMutu ID: ' . $ajukanRecord->id);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function afterSave(): void
    {
        $verifikasiRecord = $this->record;
        
        $ajukanRecord = AjukanDokumenSopMutu::find($verifikasiRecord->ajukan_dokumen_sop_mutu_id);

        if (!$ajukanRecord) {
            return;
        }

        $oldStatus = $ajukanRecord->statusProgress->status; // Simpan status lama sebelum diperbarui

        $ajukanRecord->status_progress_id = $verifikasiRecord->status_progress_id;
        $ajukanRecord->save();

        $verifikasiRecord->user_id = auth()->id(); 
        $verifikasiRecord->save();

        $statusDiterima = StatusProgress::where('status', 'Diterima')->first();
        
        if ($statusDiterima && $verifikasiRecord->status_progress_id == $statusDiterima->id) {
            $defaultStatusAktif = Status::where('status', 'Aktif')->first();
            $defaultStatusAktifId = $defaultStatusAktif ? $defaultStatusAktif->id : null;

            $filePathFromAjukan = $ajukanRecord->nama_dokumen;
            $fileNameFromAjukan = $ajukanRecord->nama_asli_dokumen;

            if (empty($filePathFromAjukan) || empty($fileNameFromAjukan)) {
                Notification::make()
                    ->title('Gagal menerbitkan dokumen.')
                    ->body('File dokumen atau nama asli dokumen tidak ditemukan di pengajuan. Pastikan dokumen sudah diunggah dengan benar.')
                    ->danger()
                    ->send();
                \Log::error('Dokumen SOP Mutu creation failed: File path or name is null for AjukanDokumenSopMutu ID: ' . $ajukanRecord->id);
                return;
            }

            try {
                $dokumenSopMutu = DokumenSopMutu::updateOrCreate(
                    ['ajukan_dokumen_sop_mutu_id' => $ajukanRecord->id],
                    [
                        'nomor_sop' => 'SOP-' . $ajukanRecord->id . '-' . sprintf('%02d', $ajukanRecord->nomor_revisi),
                        'nama_sop' => pathinfo($fileNameFromAjukan, PATHINFO_FILENAME),
                        'substansi_id' => $ajukanRecord->substansi_id,
                        'tanggal_terbit' => now(),
                        'nomor_revisi' => $ajukanRecord->nomor_revisi,
                        'status_id' => $defaultStatusAktifId,
                        'sub_kategori_sop_id' => $ajukanRecord->sub_kategori_sop_id,
                        'file_path' => $filePathFromAjukan,
                        'nama_asli_dokumen' => $fileNameFromAjukan,
                    ]
                );
                \Log::info('DokumenSopMutu created/updated successfully for AjukanDokumenSopMutu ID: ' . $ajukanRecord->id);
                // CATAT RIWAYAT: Dokumen Diterbitkan (di DokumenSopMutu)
                $dokumenSopMutu->recordHistory(
                    'Diterbitkan', 
                    'Dokumen telah disetujui dan diterbitkan sebagai SOP Mutu aktif.',
                    null,
                    'Aktif',
                    $dokumenSopMutu->nama_asli_dokumen
                );
                \Log::info('Document history recorded: Diterbitkan for DokumenSopMutu ID: ' . $dokumenSopMutu->id);

            } catch (Exception $e) {
                Notification::make()
                    ->title('Gagal menerbitkan dokumen.')
                    ->body('Terjadi kesalahan database saat menerbitkan dokumen. Silakan hubungi administrator.')
                    ->danger()
                    ->send();
                \Log::error('Database error during DokumenSopMutu updateOrCreate: ' . $e->getMessage() . ' for AjukanDokumenSopMutu ID: ' . $ajukanRecord->id);
                return;
            }
        }

        // ---------- Logika NOTIFIKASI dan CATAT RIWAYAT (setelah perubahan status) ----------
        $newStatus = $verifikasiRecord->statusProgress->status;
        $keterangan = $verifikasiRecord->keterangan;
        $pengaju = $ajukanRecord->user;
        $namaDokumen = $ajukanRecord->nama_asli_dokumen;

        $title = '';
        $body = '';
        $color = '';
        $icon = '';
        $actionDescription = '';

        switch ($newStatus) {
            case 'Diterima':
                $title = 'Dokumen Diterima';
                $body = "Dokumen '{$namaDokumen}' telah disetujui dan diterbitkan.";
                $color = 'success';
                $icon = 'heroicon-o-check-circle';
                $actionDescription = 'Dokumen disetujui dan diterbitkan.';
                break;
            case 'Ditolak':
                $title = 'Dokumen Ditolak';
                $body = "Dokumen '{$namaDokumen}' telah ditolak. Keterangan: {$keterangan}";
                $color = 'danger';
                $icon = 'heroicon-o-x-circle';
                $actionDescription = 'Dokumen ditolak. Keterangan: ' . $keterangan;
                break;
            case 'Review':
                $title = 'Dokumen Sedang Direview';
                $body = "Dokumen '{$namaDokumen}' sedang dalam proses review.";
                $color = 'info';
                $icon = 'heroicon-o-magnifying-glass';
                $actionDescription = 'Status dokumen diubah menjadi direview.';
                break;
            case 'Revisi':
                $title = 'Revisi Dokumen Diperlukan';
                $body = "Dokumen '{$namaDokumen}' memerlukan revisi. Keterangan: {$keterangan}";
                $color = 'warning';
                $icon = 'heroicon-o-pencil-square';
                $actionDescription = 'Dokumen memerlukan revisi. Keterangan: ' . $keterangan;
                break;
            default:
                return;
        }

        // CATAT RIWAYAT: Perubahan Status Verifikasi
        $ajukanRecord->recordHistory(
            'Perubahan Status Verifikasi', 
            $actionDescription,
            $oldStatus, 
            $newStatus,
            $ajukanRecord->nama_asli_dokumen // Teruskan nama asli dokumen
        );
        \Log::info('Document history recorded: Perubahan Status Verifikasi for AjukanDokumenSopMutu ID: ' . $ajukanRecord->id);

        // Notifikasi ke pengaju
        Notification::make()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->color($color)
            ->actions([
                Action::make('view_document')
                    ->label('Lihat Dokumen Pengajuan')
                    ->url(
                        route('filament.admin.resources.ajukan-dokumen-sop-mutus.create', ['record' => $ajukanRecord->id]),
                        shouldOpenInNewTab: true
                    )
                    ->icon('heroicon-o-eye'),
            ])
            ->sendToDatabase($pengaju);
    }
}
