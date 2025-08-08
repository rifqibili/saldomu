<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'mr']);
        Role::firstOrCreate(['name' => 'pengaju']);
        Role::firstOrCreate(['name' => 'staff']);
        
        // Note: Permissions will be managed through Filament Shield in the dashboard
    }
}