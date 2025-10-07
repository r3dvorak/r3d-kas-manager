<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'faktura@r3d.de'],
            [
                'name'     => 'Richard Dvořák',
                'login'    => 'RIIID',
                'password' => Hash::make('Pood.2025'),
                'role'     => 'admin',
                'is_admin' => 1,
            ]
        );
    }
}
