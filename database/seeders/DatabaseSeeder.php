<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Catalogue data — safe in every environment (production, staging, dev)
        $this->call(CatalogueSeeder::class);

        // Development / testing data — never runs in production
        if (! app()->isProduction()) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}
