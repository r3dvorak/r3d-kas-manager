<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.2.2-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Main database seeder that registers all individual seeders.
 */

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Example user
        User::factory()->create([
            'name'  => 'R3D',
            'email' => 'test@r3d.de',
        ]);

        $this->call([
            RecipeSeeder::class,
            R3dDeTestRecipeSeeder::class, // added r3d.de test recipe
        ]);
    }
}
