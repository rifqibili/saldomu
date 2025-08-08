<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
    ];

    /**
     * Get the dokumen SOP mutu with this status.
     */
    public function dokumenSopMutus(): HasMany
    {
        return $this->hasMany(DokumenSopMutu::class);
    }
}