<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $pagina_id
 * @property int|null $criado_por
 * @property int $versao
 * @property string $estado
 * @property array|null $dados_projeto
 * @property string|null $html
 * @property string|null $css
 * @property string $hash_verificacao
 * @property string|null $observacao
 */
class PageRevision extends Model
{
    protected $table = 'revisoes_pagina';

    protected static function booted(): void
    {
        static::updating(function (PageRevision $revision): void {
            if ($revision->isDirty(['dados_projeto', 'html', 'css', 'hash_verificacao', 'pagina_id', 'versao'])) {
                throw new \LogicException('O conteúdo de uma revisão é imutável.');
            }
        });
    }

    protected $fillable = [
        'pagina_id', 'criado_por', 'versao', 'estado', 'dados_projeto',
        'html', 'css', 'hash_verificacao', 'observacao',
    ];

    protected function casts(): array
    {
        return ['dados_projeto' => 'array'];
    }

    public function pagina(): BelongsTo
    {
        return $this->belongsTo(Pagina::class);
    }
}
