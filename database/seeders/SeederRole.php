<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class SeederRole extends Seeder
{

    public function run(): void
    {
        $roles = [
            ['name' => 'admin'],
            ['name' => 'member'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $this->command->info('Roles berhasil dibuat!');
    }
}
