<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VerifyMedia extends Command
{
    protected $signature = 'media:verify {--legacy}';

    protected $description = 'Confere existência, tamanho e SHA-256 dos arquivos gerenciados';

    public function handle(): int
    {
        $failures = 0;
        $verified = 0;
        $query = MediaAsset::query();
        if ($this->option('legacy')) {
            $query->where('caminho', 'like', 'media/legacy/%');
        }
        $query->each(function (MediaAsset $asset) use (&$failures, &$verified): void {
            $disk = Storage::disk($asset->disco);
            if (! $disk->exists($asset->caminho)) {
                $this->error('Arquivo ausente: '.$asset->caminho);
                $failures++;

                return;
            }
            $stream = $disk->readStream($asset->caminho);
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);
            fclose($stream);
            if (hash_final($hash) !== $asset->hash_verificacao || $disk->size($asset->caminho) !== $asset->tamanho) {
                $this->error('Arquivo divergente: '.$asset->caminho);
                $failures++;
            } else {
                $verified++;
            }
        });
        $this->info("{$verified} arquivo(s) verificado(s), {$failures} falha(s).");

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
