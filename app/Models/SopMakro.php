<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Tambahkan ini
use App\Models\Traits\HasDocumentHistory;

class SopMakro extends Model
{
    use HasFactory, SoftDeletes, HasDocumentHistory; 

    protected $table = 'sop_makros';

    protected $fillable = [
        'dokumen',
        'nama_asli_dokumen', 
        'status',
        'tahun',
    ];
}