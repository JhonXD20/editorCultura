<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteracaoTotem extends Model
{
    use HasFactory;

    protected $table = 'interacoes_totem';

    protected $fillable = [
        'totem_id',
        'pagina_id',
        'tipo',
        'duracao_segundos',
    ];

    protected $casts = [
        'duracao_segundos' => 'integer',
        'ocorrido_em' => 'datetime',
        'horario_validado' => 'boolean',
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
