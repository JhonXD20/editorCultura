<?php

namespace App\Support;

use App\Models\MediaAsset;
use App\Models\Pagina;

final class MediaReferences
{
    public static function sync(Pagina $page): void
    {
        $documents = [(string) $page->pagina_html_publicado, json_encode($page->pagina_dados_editor, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)];
        foreach ($page->revisions()->get(['html', 'dados_projeto']) as $revision) {
            $documents[] = (string) $revision->getAttribute('html');
            $documents[] = json_encode($revision->getAttribute('dados_projeto'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $combined = implode("\n", $documents);
        $ids = MediaAsset::query()->get(['id', 'caminho'])->filter(
            fn (MediaAsset $asset) => str_contains(rawurldecode($combined), '/storage/'.$asset->caminho)
        )->modelKeys();
        $page->mediaAssets()->sync($ids);
        MediaAsset::whereIn('id', $ids)->update(['ultima_referencia_em' => now()]);
    }

    public static function rebuild(): void
    {
        Pagina::query()->each(fn (Pagina $page) => self::sync($page));
    }
}
