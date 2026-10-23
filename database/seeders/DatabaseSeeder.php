<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('A carga cultural de desenvolvimento só pode ser executada em local/testing.');

            return;
        }

        DB::transaction(function (): void {
            $this->call([
                UserSeeder::class,
                CulturalMediaSeeder::class,
                CulturalContentSeeder::class,
                TotemSeeder::class,
            ]);
        });
    }
}
