<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Main seeder: runs initial (required) data only.
 * Usage: php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('InitialSeeder');
    }
}
