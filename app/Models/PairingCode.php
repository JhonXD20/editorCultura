<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $totem_id
 * @property int|null $criado_por
 * @property string $hash_codigo
 * @property Carbon $expira_em
 * @property Carbon|null $usado_em
 */
class PairingCode extends Model
{
    protected $table = 'codigos_pareamento';

    protected $fillable = ['totem_id', 'criado_por', 'hash_codigo', 'expira_em', 'usado_em'];

    protected function casts(): array
    {
        return ['expira_em' => 'datetime', 'usado_em' => 'datetime'];
    }
}
