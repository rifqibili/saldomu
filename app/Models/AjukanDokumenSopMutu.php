<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasDocumentHistory;

class AjukanDokumenSopMutu extends Model
{
    use HasFactory, SoftDeletes, HasDocumentHistory;

    protected $table = 'ajukan_dokumen_sop_mutus';

    protected $fillable = [
        'user_id',
        'substansi_id',
        'sub_kategori_sop_id',
        'nama_dokumen',
        'nama_asli_dokumen',
        'waktu_pengajuan',
        'keterangan',
        'status_progress_id',
        'nomor_revisi',
        
    ];

    /**
     * Get the user who submitted the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the substansi that the submitted document belongs to.
     */
    public function substansi(): BelongsTo
    {
        return $this->belongsTo(Substansi::class);
    }

    /**
     * Get the sub-category of the submitted document.
     */
    public function subKategoriSop(): BelongsTo
    {
        return $this->belongsTo(SubKategoriSop::class);
    }

/**
 * Get the verification records for this submission.
 */
    public function verifikasiDokumenSopMutus(): HasMany
    {
        return $this->hasMany(VerifikasiDokumenSopMutu::class);
    }
        public function statusProgress(): BelongsTo
    {
        return $this->belongsTo(StatusProgress::class);
    }
        public function dokumenSopMutu(): HasOne
    {
        return $this->hasOne(DokumenSopMutu::class, 'ajukan_dokumen_sop_mutu_id');
    }

}