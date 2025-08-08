<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubKategoriSop extends Model
{
    use HasFactory;

    protected $table = 'sub_kategori_sops';

    protected $fillable = [
        'jenis_sop',
    ];

    /**
     * Get the dokumen SOP mutu with this sub-category.
     */
    public function dokumenSopMutus(): HasMany
    {
        return $this->hasMany(DokumenSopMutu::class);
    }

    /**
     * Get the submitted documents with this sub-category.
     */
    public function ajukanDokumenSopMutus(): HasMany
    {
        return $this->hasMany(AjukanDokumenSopMutu::class);
    }
}