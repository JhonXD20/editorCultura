<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateDevice;
use App\Models\DeviceCredential;
use App\Models\InteracaoTotem;
use App\Models\MediaAsset;
use App\Models\PageRevision;
use App\Models\Pagina;
use App\Models\PairingCode;
use App\Models\Secao;
use App\Models\Totem;
use App\Models\User;
use App\Support\GrapesProjectGuard;
use App\Support\PlayerManifest;
use Database\Seeders\CulturalContentSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TotemSyncSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->withoutVite();
    }

    public function test_seed_builds_the_published_cultural_collection_and_preserves_login_contract(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 4);
        foreach (UserSeeder::ACCOUNTS as [$name, $email, $password, $role]) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertSame($role, $user->perfil);
            $this->assertTrue($user->ativo);
            $this->assertTrue(Hash::check($password, $user->password));
            $this->assertFalse($user->convite_pendente);
        }
        $this->post(route('login'), ['email' => 'demo@editorcultura.test', 'password' => 'password'])
            ->assertRedirect(route('admin'));

        $this->assertSame(2, Secao::count());
        $this->assertSame(7, Pagina::count());
        $this->assertSame(7, PageRevision::count());
        $this->assertSame(4, MediaAsset::count());
        $definitions = require database_path('seeders/content/cultural.php');
        foreach (Secao::all() as $section) {
            $this->assertSame([1, 2, 3], $section->paginas->pluck('ordem_slide')->all());
            $this->assertSame(array_column($definitions[$section->nome], 'title'), $section->paginas->pluck('pagina_titulo')->all());
        }
        foreach (Pagina::all() as $page) {
            $revision = $page->publishedRevision;
            $this->assertSame('published', $page->pagina_estado_editorial);
            $this->assertTrue($page->pagina_ativo);
            $this->assertNotNull($page->pagina_publicada_em);
            $this->assertSame(1, $page->pagina_versao_publicacao);
            $this->assertSame('published', $revision->estado);
            $this->assertSame($page->id, $revision->pagina_id);
            $this->assertSame($page->pagina_dados_editor, $revision->dados_projeto);
            $this->assertSame($page->pagina_html_publicado, $revision->html);
            $this->assertSame($page->pagina_css_publicado, $revision->css);
            $this->assertSame(hash('sha256', json_encode([$revision->dados_projeto, $revision->html, $revision->css], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), $revision->hash_verificacao);
            GrapesProjectGuard::validate($page->pagina_dados_editor);
            $this->assertNotEmpty($page->pagina_dados_editor['styles']);
            $this->assertStringContainsString('data-cultura-layout="fixed-screen"', $page->pagina_html_publicado);
            $this->assertStringNotContainsString('overflow-y:auto', $page->pagina_css_publicado);
            $this->assertNotEmpty($page->mediaAssets);
            foreach ($page->mediaAssets as $asset) {
                Storage::disk('public')->assertExists($asset->caminho);
                $this->assertSame($asset->hash_verificacao, hash('sha256', Storage::disk('public')->get($asset->caminho)));
                $this->assertGreaterThanOrEqual(1000, $asset->metadados['width']);
            }
        }
        $home = Pagina::where('pagina_tipo_layout', 'home')->sole();
        foreach (Secao::all() as $section) {
            $this->assertStringContainsString('data-section-id="'.$section->id.'"', $home->pagina_html_publicado);
        }
        $this->assertSame(0, InteracaoTotem::count());
    }

    public function test_all_three_units_can_pair_and_serve_every_page_and_media_in_the_manifest(): void
    {
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
        preg_match_all('~/parear/(\d{6})~', Artisan::output(), $matches);
        $this->assertCount(3, $matches[1]);
        $this->assertSame(3, Totem::count());
        $this->assertSame(3, PairingCode::count());

        foreach ($matches[1] as $code) {
            $pairing = PairingCode::where('hash_codigo', hash('sha256', $code))->sole();
            $response = $this->post(route('device.pair'), ['code' => $code]);
            $home = Totem::findOrFail($pairing->totem_id)->paginaInicialDisponivel();
            $response->assertRedirect(route('totem.exibir', $home))->assertCookie(AuthenticateDevice::COOKIE);
            $token = $response->getCookie(AuthenticateDevice::COOKIE, false)->getValue();
            $this->assertDatabaseHas('credenciais_dispositivo', ['totem_id' => $pairing->totem_id, 'hash_token' => hash('sha256', $token)]);
            $manifest = $this->withToken($token)->getJson(route('device.manifest'))->assertOk()
                ->assertJsonCount(7, 'pages')->assertJsonCount(2, 'sections')
                ->assertJsonPath('totem.home_page_id', $home->id)->assertJsonPath('totem.orientation', 'landscape')
                ->assertJsonPath('totem.idle_seconds', 120)->json();
            foreach (MediaAsset::all() as $asset) {
                $this->assertContains('/storage/'.$asset->caminho, $manifest['assets']);
            }
            foreach (Pagina::all() as $page) {
                $this->withToken($token)->get(route('totem.exibir', $page))->assertOk()->assertSee($page->pagina_titulo);
            }
            $this->withToken($token)->postJson(route('device.heartbeat'))->assertOk();
            $this->assertSame('online', Totem::findOrFail($pairing->totem_id)->status_monitoramento);
            $this->post(route('device.pair'), ['code' => $code])->assertSessionHasErrors('code');
        }
        $this->assertSame(3, DeviceCredential::count());
    }

    public function test_reseeding_preserves_passwords_edits_revisions_pairings_and_manifest_version(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'demo@editorcultura.test')->sole();
        $user->update(['password' => 'senha-alterada-pelo-administrador']);
        $page = Pagina::where('pagina_tipo_layout', 'interna')->firstOrFail();
        $page->update(['pagina_versao_rascunho' => 2, 'pagina_estado_editorial' => 'draft']);
        $before = [User::count(), Secao::count(), Pagina::count(), PageRevision::count(), MediaAsset::count(), Totem::count(), PairingCode::count()];
        $version = PlayerManifest::make(Totem::firstOrFail())['version'];
        $this->seed(DemoSeeder::class);
        $this->assertSame($before, [User::count(), Secao::count(), Pagina::count(), PageRevision::count(), MediaAsset::count(), Totem::count(), PairingCode::count()]);
        $this->assertTrue(Hash::check('senha-alterada-pelo-administrador', $user->fresh()->password));
        $this->assertSame('draft', $page->fresh()->pagina_estado_editorial);
        $this->assertSame(2, $page->fresh()->pagina_versao_rascunho);
        $this->assertSame($version, PlayerManifest::make(Totem::firstOrFail())['version']);
    }

    public function test_development_accounts_and_collection_are_not_seeded_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->app->make(DatabaseSeeder::class)->run();
        $this->assertSame(0, User::count());
        $this->assertSame(0, Pagina::count());
        $this->assertSame(0, Totem::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_explicit_layout_refresh_keeps_old_revisions_and_advances_publication_versions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $originalRevisions = PageRevision::all()->mapWithKeys(fn (PageRevision $revision) => [$revision->id => $revision->hash_verificacao])->all();
        $userPasswords = User::pluck('password', 'id')->all();
        $totemIds = Totem::pluck('id')->all();

        $this->app->make(CulturalContentSeeder::class)->run(refreshExisting: true);

        $this->assertSame(7, Pagina::count());
        $this->assertSame(14, PageRevision::count());
        foreach ($originalRevisions as $id => $checksum) {
            $this->assertSame($checksum, PageRevision::findOrFail($id)->hash_verificacao);
        }
        foreach (Pagina::all() as $page) {
            $this->assertSame(2, $page->pagina_versao_publicacao);
            $this->assertSame(2, $page->pagina_versao_rascunho);
            $this->assertSame(2, $page->publishedRevision->versao);
        }
        $this->assertSame($userPasswords, User::pluck('password', 'id')->all());
        $this->assertSame($totemIds, Totem::pluck('id')->all());
    }
}
