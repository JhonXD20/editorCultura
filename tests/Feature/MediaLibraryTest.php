<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_async_delete_removes_file_and_returns_the_deleted_id(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/images/deletable.png', 'image');
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(), 'disco' => 'public', 'caminho' => 'media/images/deletable.png',
            'nome_original' => 'deletable.png', 'tipo_mime' => 'image/png', 'tamanho' => 5,
            'hash_verificacao' => hash('sha256', 'image'), 'tipo' => 'image',
        ]);
        $this->actingAs(User::factory()->create(['perfil' => 'editor']))
            ->deleteJson(route('midias.destroy', $asset))->assertOk()->assertJsonPath('deleted_id', $asset->id);
        $this->assertDatabaseMissing('midias', ['id' => $asset->id]);
        Storage::disk('public')->assertMissing($asset->caminho);
    }

    public function test_public_media_urls_do_not_depend_on_the_configured_app_host(): void
    {
        $this->withoutVite();
        config(['app.url' => 'http://localhost']);
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(), 'disco' => 'public', 'caminho' => 'media/images/imagem.png',
            'nome_original' => 'Imagem de teste.png', 'tipo_mime' => 'image/png',
            'tamanho' => 123, 'hash_verificacao' => hash('sha256', 'test'), 'tipo' => 'image',
        ]);

        $this->assertSame('/storage/'.$asset->caminho, Storage::disk('public')->url($asset->caminho));
        $this->actingAs(User::factory()->create())
            ->get('http://editorcultura.test/midias')
            ->assertOk()
            ->assertSee('src="/storage/media/images/imagem.png"', false)
            ->assertSee('alt="Imagem de teste.png"', false)
            ->assertDontSee('src="http://localhost/storage/', false);
    }
}
