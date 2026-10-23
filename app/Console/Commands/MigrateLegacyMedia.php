<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\Pagina;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class MigrateLegacyMedia extends Command
{
    protected $signature = 'media:migrate-legacy {--dry-run}';

    protected $description = 'Move uploads históricos do diretório público para o disco de mídia gerenciado';

    public function handle(): int
    {
        $source = public_path('totem/img/uploads');
        $files = is_dir($source) ? collect(scandir($source))->reject(fn (string $name) => in_array($name, ['.', '..', '.gitignore'], true)) : collect();
        $migrated = 0;
        $failed = 0;

        foreach ($files as $name) {
            $absolute = $source.DIRECTORY_SEPARATOR.$name;
            $contents = is_file($absolute) && is_readable($absolute) ? file_get_contents($absolute) : false;
            if ($contents === false) {
                $process = new Process(['git', 'show', 'HEAD:public/totem/img/uploads/'.$name], base_path());
                $process->run();
                $contents = $process->isSuccessful() ? $process->getOutput() : false;
            }
            if ($contents === false) {
                $this->error("Não foi possível recuperar: {$name}");
                $failed++;

                continue;
            }
            $target = 'media/legacy/'.$name;
            $oldUrl = '/totem/img/uploads/'.rawurlencode($name);
            $newUrl = '/storage/'.$target;
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream';
            $this->line("{$oldUrl} -> {$newUrl}");

            if (! $this->option('dry-run')) {
                Storage::disk('public')->put($target, $contents);
                $asset = MediaAsset::firstOrCreate(['caminho' => $target], [
                    'uuid' => (string) Str::uuid(),
                    'disco' => 'public',
                    'nome_original' => $name,
                    'tipo_mime' => $mime,
                    'tamanho' => strlen($contents),
                    'hash_verificacao' => hash('sha256', $contents),
                    'tipo' => str_starts_with($mime, 'video/') ? 'video' : 'image',
                ]);

                Pagina::query()->each(function (Pagina $page) use ($oldUrl, $newUrl, $asset, $name): void {
                    $projectJson = json_encode($page->pagina_dados_editor, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $html = (string) $page->pagina_html_publicado;
                    $changed = false;
                    foreach ([$oldUrl, '/totem/img/uploads/'.$name] as $candidate) {
                        if ($projectJson && str_contains($projectJson, $candidate)) {
                            $projectJson = str_replace($candidate, $newUrl, $projectJson);
                            $changed = true;
                        }
                        if (str_contains($html, $candidate)) {
                            $html = str_replace($candidate, $newUrl, $html);
                            $changed = true;
                        }
                    }
                    if ($changed) {
                        $page->update([
                            'pagina_dados_editor' => json_decode((string) $projectJson, true),
                            'pagina_html_publicado' => $html ?: null,
                            'pagina_versao_rascunho' => $page->pagina_versao_rascunho + 1,
                        ]);
                        $page->mediaAssets()->syncWithoutDetaching([$asset->id]);
                    }
                });
            }
            $migrated++;
        }

        $this->info(($this->option('dry-run') ? 'Simulação: ' : '')."{$migrated} arquivo(s) processado(s); {$failed} falha(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
