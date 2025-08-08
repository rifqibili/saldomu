<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Filament\Panel; 
use Filament\Models\Contracts\FilamentUser; // Pastikan ini diimpor dengan benar

class User extends Authenticatable implements FilamentUser // Pastikan mengimplementasikan FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'nip',
        'jabatan',
        'substansi_id',
        'email',
        'password',
        'avatar_url', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the substansi that the user belongs to.
     */
    public function substansi(): BelongsTo
    {
        return $this->belongsTo(Substansi::class);
    }

    /**
     * Get the documents submitted by the user.
     */
    public function ajukanDokumenSopMutus(): HasMany
    {
        return $this->hasMany(AjukanDokumenSopMutu::class);
    }

    /**
     * Get the documents verified by the user.
     */
    public function verifikasiDokumenSopMutus(): HasMany
    {
        return $this->hasMany(VerifikasiDokumenSopMutu::class);
    }

    /**
     * Get the URL for the user's avatar.
     */
    public function getAvatarUrlAttribute(): string
    {
        // Pastikan 'avatar_url' ada dan bukan null sebelum memanggil Storage::url
        return $this->attributes['avatar_url'] ? Storage::disk('public')->url($this->attributes['avatar_url']) : '';
    }

    /**
     * Metode ini diperlukan oleh interface FilamentUser.
     * Mengatur apakah pengguna dapat mengakses panel admin Filament.
     *
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool // <--- PERBAIKAN UTAMA: Ganti canAccessFilament() menjadi canAccessPanel()
    {
        // Anda bisa menambahkan logika otorisasi di sini,
        // misalnya berdasarkan peran (role) pengguna.
        // Contoh: return $this->hasRole('admin');
        
        // Untuk saat ini, kita kembalikan true agar bisa diakses
        return true; 
    }
}