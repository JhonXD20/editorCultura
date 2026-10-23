<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int|null $enviado_por
 * @property string $disco
 * @property string $caminho
 * @property string $nome_original
 * @property string $tipo_mime
 * @property int $tamanho
 * @property string $hash_verificacao
 * @property string $tipo
 * @property array|null $metadados
 * @property Carbon|null $ultima_referencia_em
 */
class MediaAsset extends Model
{
    protected $table = 'midias';

    protected $fillable = [
        'uuid', 'enviado_por', 'disco', 'caminho', 'nome_original', 'tipo_mime',
        'tamanho', 'hash_verificacao', 'tipo', 'metadados', 'ultima_referencia_em',
    ];

    protected function casts(): array
    {
        return ['metadados' => 'array', 'ultima_referencia_em' => 'datetime'];
    }

    public function paginas(): BelongsToMany
    {
        return $this->belongsToMany(Pagina::class, 'referencias_midia', 'midia_id', 'pagina_id')->withTimestamps();
    }
}
