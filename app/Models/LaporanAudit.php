<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 
use App\Models\Traits\HasDocumentHistory;

class LaporanAudit extends Model
{
    use HasFactory, SoftDeletes, HasDocumentHistory;

    protected $fillable = [
        'dokumen',
        'nama_asli_dokumen', 
        'tahun',
    ];
}