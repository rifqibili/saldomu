<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasDocumentHistory;

class DokumenSopMutu extends Model
{
    use HasFactory, SoftDeletes, HasDocumentHistory;

    protected $table = 'dokumen_sop_mutus';
    protected $guarded = []; 

    protected $fillable = [
        'nomor_sop',
        'nama_sop',
        'substansi_id',
        'tanggal_terbit',
        'status_id',
        'sub_kategori_sop_id',
        'waktu_pengajuan',
        'nomor_revisi',
        'ajukan_dokumen_sop_mutu_id',
        'dokumen',
        'nama_asli_dokumen',
        'file_path',
    ];

    /**
     * Get the substansi that the document belongs to.
     */
    public function substansi(): BelongsTo
    {
        return $this->belongsTo(Substansi::class);
    }

    /**
     * Get the status of the document.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the sub-category of the document.
     */
    public function subKategoriSop(): BelongsTo
    {
        return $this->belongsTo(SubKategoriSop::class);
    }

    public function ajukanDokumenSopMutu(): BelongsTo
    {
        return $this->belongsTo(AjukanDokumenSopMutu::class);
    }
}