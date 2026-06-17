<?php

namespace Database\Seeders;

use App\Admin\Database\Seeders\AdminAccessSeeder;
use App\Models\User;
use App\Modules\Catalog\Database\Seeders\CatalogSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin domain: roles, permissions and the super-admin account.
        $this->call(AdminAccessSeeder::class);

        User::firstOrCreate(
            ['email' => 'retail@example.com'],
            ['name' => 'Retail User', 'password' => bcrypt('password')],
        );

        User::firstOrCreate(
            ['email' => 'wholesale@example.com'],
            ['name' => 'Wholesale User', 'password' => bcrypt('password'), 'customer_type' => User::TYPE_WHOLESALE, 'wholesale_status' => User::WHOLESALE_APPROVED],
        );

        $this->call(CatalogSeeder::class);
    }
}
