<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SeederDatabase extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'caca@gmail.com',
            'role_id' => 1,
            'password' => Hash::make('123'),
        ]);
    }
}
