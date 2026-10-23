<?php

namespace App\Console\Commands;

use App\Models\Pagina;
use App\Support\PublishedPageSanitizer;
use Illuminate\Console\Command;

class MigrateLegacyContent extends Command
{
    protected $signature = 'content:migrate-legacy {--dry-run} {--page=} {--all}';

    protected $description = 'Converte páginas do editor legado em rascunhos GrapesJS sem sobrescrever conteúdo migrado';

    public function handle(): int
    {
        if (! $this->option('all') && ! $this->option('page')) {
            $this->error('Informe --all ou --page=ID.');

            return self::INVALID;
        }

        $query = Pagina::with('conteudos')->whereNull('pagina_dados_editor');
        if ($this->option('page')) {
            $query->whereKey((int) $this->option('page'));
        }

        $migrated = 0;
        $query->orderBy('id')->each(function (Pagina $pagina) use (&$migrated): void {
            $html = PublishedPageSanitizer::html(view('partials.canvas', [
                'pagina' => $pagina,
                'editavel' => false,
            ])->render());
            $project = [
                'assets' => [],
                'styles' => [],
                'pages' => [[
                    'id' => 'page-'.$pagina->id,
                    'name' => $pagina->pagina_titulo,
                    'component' => $html,
                ]],
            ];

            $this->line("#{$pagina->id} {$pagina->pagina_titulo}: ".strlen($html).' bytes');
            if (! $this->option('dry-run')) {
                $pagina->update([
                    'pagina_dados_editor' => $project,
                    'pagina_versao_editor' => 'grapesjs-0.23-migrated',
                    'pagina_estado_editorial' => 'draft',
                    'pagina_versao_rascunho' => $pagina->pagina_versao_rascunho + 1,
                ]);
            }
            $migrated++;
        });

        $this->info(($this->option('dry-run') ? 'Simulação: ' : '')."{$migrated} página(s) processada(s).");

        return self::SUCCESS;
    }
}
