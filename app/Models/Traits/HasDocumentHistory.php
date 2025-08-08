<?php

namespace App\Models\Traits;

use App\Models\DocumentHistory;

trait HasDocumentHistory
{
    /**
     * Get all of the document's history.
     */
    public function histories()
    {
        return $this->morphMany(DocumentHistory::class, 'documentable');
    }

    /**
     * Record a new history entry for the document.
     */
    public function recordHistory(string $action, ?string $description = null, ?string $oldStatus = null, ?string $newStatus = null, ?string $namaAsliDokumen = null): DocumentHistory // <-- Tambahkan parameter
    {
        // Jika namaAsliDokumen tidak disediakan, coba ambil dari properti model
        if (is_null($namaAsliDokumen)) {
            $namaAsliDokumen = $this->nama_asli_dokumen ?? $this->nama_sop ?? null;
        }

        return $this->histories()->create([
            'user_id' => auth()->id() ?? null,
            'action' => $action,
            'description' => $description,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'nama_asli_dokumen' => $namaAsliDokumen, // <-- Simpan ini
        ]);
    }
}
