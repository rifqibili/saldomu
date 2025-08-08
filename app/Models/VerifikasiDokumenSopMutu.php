<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes; 
use App\Models\Traits\HasDocumentHistory;

class VerifikasiDokumenSopMutu extends Model
{
    use HasFactory, SoftDeletes, HasDocumentHistory;

    protected $table = 'verifikasi_dokumen_sop_mutus';

    protected $fillable = [
        'user_id',
        'ajukan_dokumen_sop_mutu_id',
        'status_progress_id',
        'keterangan',
    ];

    /**
     * Get the user who verified the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the submission record that was verified.
     */
    public function ajukanDokumenSopMutu(): BelongsTo
    {
        return $this->belongsTo(AjukanDokumenSopMutu::class);
    }

    /**
     * Get the status progress of the verification.
     */
    public function statusProgress(): BelongsTo
    {
        return $this->belongsTo(StatusProgress::class);
    }
}