<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusProgress extends Model
{
    use HasFactory;
    protected $table = 'status_progresses';
    protected $fillable = [
        'status',
    ];

    /**
     * Get the verification documents with this status progress.
     */
    public function verifikasiDokumenSopMutus(): HasMany
    {
        return $this->hasMany(VerifikasiDokumenSopMutu::class);
    }
}