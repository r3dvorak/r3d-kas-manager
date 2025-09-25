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
use App\Models\KasClient;

class KasClientSeeder extends Seeder
{
    public function run(): void
    {
        KasClient::create([
            'name'        => 'Demo Client',
            'api_user'    => 'w01e77bc',
            'api_password'=> env('KAS_PASSWORD', 'srrR3wo2qckkDEZRwkxq'),
            'api_url'     => 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl',
        ]);
    }
}
