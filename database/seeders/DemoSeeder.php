<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Compatibility entry point for composer demo and existing local scripts. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
    }
}
