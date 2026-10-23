<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $totem_recarga_solicitada_em
 * @property bool $questionario_ativo
 */
class Totem extends Model
{
    use HasFactory;

    protected $table = 'totens';

    protected $fillable = [
        'totem_nome_identificacao',
        'totem_localizacao',
        'totem_ping',
        'pagina_inicial_id',
        'totem_orientacao',
        'totem_inatividade_segundos',
        'totem_recarga_solicitada_em',
        'questionario_ativo',
    ];

    protected $casts = [
        'totem_ping' => 'datetime',
        'pagina_inicial_id' => 'integer',
        'totem_inatividade_segundos' => 'integer',
        'totem_recarga_solicitada_em' => 'datetime',
        'questionario_ativo' => 'boolean',
    ];

    public function paginaInicialDisponivel(): ?Pagina
    {
        $configurada = $this->pagina_inicial_id
            ? Pagina::where('pagina_ativo', true)->whereNotNull('pagina_publicada_em')->find($this->pagina_inicial_id)
            : null;

        return $configurada ?? self::paginaInicialPadrao();
    }

    public static function paginaInicialPadrao(): ?Pagina
    {
        return Pagina::where('pagina_ativo', true)
            ->whereNotNull('pagina_publicada_em')
            ->orderByRaw("CASE WHEN pagina_tipo_layout = 'home' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    /**
     * Verifica se o totem está online.
     *
     * Considera online quando recebeu
     * um ping nos últimos 120 segundos.
     */
    public function getStatusMonitoramentoAttribute(): string
    {
        if (! $this->totem_ping) {
            return 'offline';
        }

        return $this->totem_ping->gte(
            now()->subSeconds(120)
        )
            ? 'online'
            : 'offline';
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(DeviceCredential::class);
    }
}
