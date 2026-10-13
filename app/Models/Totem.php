<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Totem extends Model
{
    use HasFactory;
    protected $table = 'totens';

    protected $fillable = [
        'totem_nome_identificacao',
        'totem_localizacao',
        'totem_ping',
    ];

    protected $casts = [
        'totem_ping' => 'datetime',
    ];

    /**
     * Verifica se o totem está online.
     *
     * Considera online quando recebeu
     * um ping nos últimos 120 segundos.
     */
    public function getStatusMonitoramentoAttribute(): string
    {
        if (!$this->totem_ping) {
            return 'offline';
        }

        return $this->totem_ping->gte(
            now()->subSeconds(120)
        )
            ? 'online'
            : 'offline';
    }
}
