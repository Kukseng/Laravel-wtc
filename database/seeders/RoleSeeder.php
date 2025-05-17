<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Customer']);
        Role::create(['name' => 'Warehouse Manager']);
        Role::create(['name' => 'Staff']);
    }
}