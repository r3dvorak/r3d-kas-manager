<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.1-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin-User
        User::create([
            'name' => 'RIIID',
            'login' => 'w01e77bc', // 👈 API-User/Login
            'email' => 'admin@example.com', // optional
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}
