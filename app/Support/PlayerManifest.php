<?php

namespace App\Support;

use App\Models\Pagina;
use App\Models\Secao;
use App\Models\Totem;

final class PlayerManifest
{
    public static function make(Totem $totem): array
    {
        $pages = Pagina::query()->where('pagina_ativo', true)->whereNotNull('pagina_publicada_em')
            ->orderBy('id')->get();
        $settings = [
            'id' => $totem->id,
            'orientation' => $totem->totem_orientacao,
            'idle_seconds' => $totem->totem_inatividade_segundos,
            'home_page_id' => $totem->paginaInicialDisponivel()?->id,
            'reload_requested_at' => $totem->totem_recarga_solicitada_em?->toIso8601String(),
            'questionnaire_enabled' => $totem->questionario_ativo,
        ];
        $assets = ['/vendor/fontawesome-free/css/all.min.css'];
        foreach ($pages as $page) {
            preg_match_all('~(?:src|poster)=["\'](/(?!/)[^"\']+)["\']~i', (string) $page->pagina_html_publicado, $matches);
            $assets = array_merge($assets, $matches[1]);
        }
        $pageData = $pages->map(fn (Pagina $page) => [
            'id' => $page->id,
            'version' => $page->pagina_versao_publicacao,
            'checksum' => hash('sha256', $page->pagina_html_publicado.'|'.$page->pagina_css_publicado),
            'section_id' => $page->secao_id,
            'sequence' => $page->ordem_slide,
            'legacy_group' => $page->grupo_slide,
            'url' => route('totem.exibir', $page),
        ])->all();
        $sections = Secao::whereIn('id', $pages->pluck('secao_id')->filter())->orderBy('id')
            ->get(['id', 'nome'])->toArray();
        $version = hash('sha256', json_encode([$settings, $pageData, $sections, config('app.release', 'development')], JSON_THROW_ON_ERROR));

        return ['version' => $version, 'totem' => $settings, 'pages' => $pageData, 'sections' => $sections, 'assets' => array_values(array_unique($assets))];
    }
}
