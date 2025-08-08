<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Substansi extends Model
{
    use HasFactory;

    protected $fillable = [
        'substansi',
    ];

    /**
     * Get the users for the substansi.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the dokumen SOP mutu for the substansi.
     */
    public function dokumenSopMutus(): HasMany
    {
        return $this->hasMany(DokumenSopMutu::class);
    }

    /**
     * Get the submitted documents for the substansi.
     */
    public function ajukanDokumenSopMutus(): HasMany
    {
        return $this->hasMany(AjukanDokumenSopMutu::class);
    }
}