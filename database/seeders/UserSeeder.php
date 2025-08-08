<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Substansi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $substansiId = Substansi::first()->id;

        // User Admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nama' => 'Admin Utama',
                'nip' => 'ADM001',
                'jabatan' => 'Administrator',
                'substansi_id' => $substansiId,
                'password' => Hash::make('123'),
            ]
        );
        $adminUser->assignRole('admin');
        
        // User MR
        $mrUser = User::firstOrCreate(
            ['email' => 'mr@example.com'],
            [
                'nama' => 'Manajemen Representatif',
                'nip' => 'MR001',
                'jabatan' => 'MR',
                'substansi_id' => $substansiId,
                'password' => Hash::make('123'),
            ]
        );
        $mrUser->assignRole('mr');

        // User Pengaju
        $pengajuUser = User::firstOrCreate(
            ['email' => 'pengaju@example.com'],
            [
                'nama' => 'Pengaju Dokumen',
                'nip' => 'PENG001',
                'jabatan' => 'Staff',
                'substansi_id' => $substansiId,
                'password' => Hash::make('123'),
            ]
        );
        $pengajuUser->assignRole('pengaju');

        // User Staff
        $staffUser = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'nama' => 'Staff Biasa',
                'nip' => 'STF001',
                'jabatan' => 'Staff',
                'substansi_id' => $substansiId,
                'password' => Hash::make('123'),
            ]
        );
        $staffUser->assignRole('staff');
    }
}