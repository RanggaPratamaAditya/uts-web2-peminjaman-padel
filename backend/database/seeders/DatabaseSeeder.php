<?php

namespace Database\Seeders;

use App\Models\Lapangan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Padel',
            'email' => 'admin@padel.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'User Padel',
            'email' => 'user@padel.test',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        Lapangan::create([
            'nama_lapangan' => 'Court 1',
            'tipe' => 'Indoor',
            'harga_per_jam' => 100000,
            'status' => 'tersedia',
        ]);

        Lapangan::create([
            'nama_lapangan' => 'Court 2',
            'tipe' => 'Outdoor',
            'harga_per_jam' => 80000,
            'status' => 'tersedia',
        ]);

        Lapangan::create([
            'nama_lapangan' => 'Court 3',
            'tipe' => 'Indoor',
            'harga_per_jam' => 120000,
            'status' => 'tersedia',
        ]);
    }
}