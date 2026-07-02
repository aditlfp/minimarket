<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        collect(['admin', 'manajer', 'kasir', 'staff_gudang'])->each(fn($r) => Role::create(['name' => $r]));
    }
}
