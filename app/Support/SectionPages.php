<?php

namespace App\Support;

use App\Models\Pagina;
use App\Models\Secao;

final class SectionPages
{
    // Use the same lock order for membership changes and drag-and-drop ordering.
    public static function lock(): void
    {
        Secao::query()->orderBy('id')->lockForUpdate()->get(['id']);
    }

    public static function move(Pagina $page, ?int $sectionId): void
    {
        $previous = $page->secao_id;
        if ($previous === $sectionId) {
            return;
        }
        $page->secao_id = $sectionId;
        $page->ordem_slide = $sectionId === null ? 0
            : ((int) Pagina::where('secao_id', $sectionId)->max('ordem_slide')) + 1;
        // Avoid inadvertently rejoining a legacy sequence when made standalone.
        $page->grupo_slide = null;
        Secao::whereIn('id', array_filter([$previous, $sectionId]))->increment('versao');
    }
}
