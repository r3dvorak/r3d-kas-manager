<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.4.4-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Erstellt den Admin-Benutzer
        User::create([
            'name' => 'RIIID',
            'email' => 'admin@example.com', // später ggf. echte Mailadresse setzen
            'password' => bcrypt('Pood.2025'),
            'is_admin' => true,
        ]);

        // Ruft den KasClientSeeder auf
        $this->call([
            KasClientSeeder::class,
        ]);
    }
}
