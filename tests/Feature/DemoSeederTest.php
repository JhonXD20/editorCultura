<?php

namespace Tests\Feature;

use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_builds_a_complete_visualization_scenario(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'demo@editorcultura.test']);
        $this->assertSame(5, Pagina::where('pagina_titulo', 'like', '[DEMO]%')->count());
        $this->assertSame(6, Totem::where('totem_nome_identificacao', 'like', '[DEMO]%')->count());
        $this->assertGreaterThan(300, InteracaoTotem::count());
        $this->assertTrue(Pagina::whereNotNull('published_at')->exists());
        $this->assertTrue(Pagina::whereNull('published_at')->exists());
        $this->assertTrue(Pagina::where('pagina_ativo', false)->exists());
        $this->assertTrue(Totem::all()->contains(fn (Totem $totem): bool => $totem->status_monitoramento === 'online'));
        $this->assertTrue(Totem::all()->contains(fn (Totem $totem): bool => $totem->status_monitoramento === 'offline'));
        $this->assertTrue(User::where('email', 'demo@editorcultura.test')->exists());
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        $this->seed(DemoSeeder::class);
        $firstCounts = [Pagina::count(), Totem::count(), InteracaoTotem::count()];

        $this->seed(DemoSeeder::class);

        $this->assertSame($firstCounts, [Pagina::count(), Totem::count(), InteracaoTotem::count()]);
    }
}
