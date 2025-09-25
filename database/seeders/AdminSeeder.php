<?php
/*
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.5.0-alpha
 * @date      2025-09-25
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'RIIID',
            'email'    => 'admin@example.com',
            'password' => Hash::make('Pood.2025'),
            'role'     => 'admin',
            'kas_client_id' => null,
        ]);
    }
}
