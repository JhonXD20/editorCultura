<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteracaoTotem extends Model
{
    protected $table = 'interacoes_totem';

    protected $fillable = [
        'totem_id',
        'pagina_id',
        'tipo',
        'duracao_segundos',
    ];

    protected $casts = [
        'duracao_segundos' => 'integer',
    ];

    public function totem(): BelongsTo
    {
        return $this->belongsTo(Totem::class, 'totem_id');
    }

    public function pagina(): BelongsTo
    {
        return $this->belongsTo(Pagina::class, 'pagina_id');
    }
}
