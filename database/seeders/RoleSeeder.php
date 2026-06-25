<?php

namespace Database\Seeders;

use App\Support\RolePermissionSynchronizer;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        RolePermissionSynchronizer::sync();
    }
}
