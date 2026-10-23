<?php

namespace Database\Seeders;

use App\Models\PageRevision;
use App\Models\Pagina;
use App\Models\Secao;
use App\Models\User;
use App\Support\GrapesProjectGuard;
use App\Support\MediaReferences;
use App\Support\PublishedPageSanitizer;
use Database\Seeders\Support\CulturalPageLayout;
use Illuminate\Database\Seeder;

class CulturalContentSeeder extends Seeder
{
    public function run(bool $refreshExisting = false): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $definitions = require __DIR__.'/content/cultural.php';
        $routes = [];
        foreach ($definitions as $name => $pages) {
            $section = Secao::firstOrCreate(['nome' => $name]);
            foreach ($pages as $index => $definition) {
                $page = Pagina::firstOrCreate(['pagina_titulo' => $definition['title']], [
                    'pagina_tipo_layout' => 'interna', 'secao_id' => $section->id,
                    'grupo_slide' => $name, 'ordem_slide' => $index + 1, 'pagina_ativo' => true,
                ]);
                if ($page->wasRecentlyCreated || $refreshExisting) {
                    $this->publish($page, CulturalPageLayout::article($definition, $name, $index + 1));
                }
            }
            $routes[] = ['id' => $section->id, 'name' => $name, 'summary' => $pages[0]['summary']];
        }

        $home = Pagina::firstOrCreate(['pagina_tipo_layout' => 'home'], [
            'pagina_titulo' => 'Tocantins: caminhos de memória', 'pagina_ativo' => true,
            'ordem_slide' => 0,
        ]);
        if ($home->wasRecentlyCreated || $refreshExisting) {
            $this->publish($home, CulturalPageLayout::home($routes));
        }
    }

    private function publish(Pagina $page, array $document): void
    {
        GrapesProjectGuard::validate($document['project']);
        $html = PublishedPageSanitizer::html($document['html']);
        $css = PublishedPageSanitizer::css($document['css']);
        $checksum = hash('sha256', json_encode([$document['project'], $html, $css], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $version = ((int) $page->revisions()->max('versao')) + 1;
        $revision = PageRevision::create([
            'pagina_id' => $page->id, 'criado_por' => User::where('email', 'demo@editorcultura.test')->value('id'),
            'versao' => $version, 'estado' => 'published', 'dados_projeto' => $document['project'],
            'html' => $html, 'css' => $css, 'hash_verificacao' => $checksum,
            'observacao' => $version === 1 ? 'Publicação inicial do acervo cultural TotemSync.' : 'Acervo cultural TotemSync: adequação à tela sem rolagem.',
        ]);
        $page->update([
            'pagina_dados_editor' => $document['project'], 'pagina_html_publicado' => $html,
            'pagina_css_publicado' => $css, 'pagina_versao_editor' => 'grapesjs-0.23',
            'pagina_versao_rascunho' => $page->wasRecentlyCreated ? 1 : $page->pagina_versao_rascunho + 1,
            'pagina_versao_publicacao' => $page->pagina_versao_publicacao + 1,
            'pagina_estado_editorial' => 'published', 'pagina_publicada_em' => now(),
            'revisao_publicada_id' => $revision->id,
        ]);
        MediaReferences::sync($page);
    }
}
