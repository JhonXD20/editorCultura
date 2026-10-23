<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $pagina_id
 * @property int $revisao_pagina_id
 * @property int|null $enviado_por
 * @property int|null $revisado_por
 * @property string $situacao
 * @property string|null $comentario_revisao
 * @property Carbon $enviado_em
 * @property Carbon|null $revisado_em
 * @property-read Pagina $pagina
 * @property-read PageRevision $revision
 */
class ReviewRequest extends Model
{
    protected $table = 'solicitacoes_revisao';

    protected $fillable = [
        'pagina_id', 'revisao_pagina_id', 'enviado_por', 'revisado_por',
        'situacao', 'comentario_revisao', 'enviado_em', 'revisado_em',
    ];

    protected function casts(): array
    {
        return ['enviado_em' => 'datetime', 'revisado_em' => 'datetime'];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(PageRevision::class, 'revisao_pagina_id');
    }

    public function pagina(): BelongsTo
    {
        return $this->belongsTo(Pagina::class);
    }
}
