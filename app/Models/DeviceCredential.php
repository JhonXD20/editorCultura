<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $totem_id
 * @property string $nome
 * @property string $hash_token
 * @property Carbon|null $ultimo_contato_em
 * @property Carbon|null $revogado_em
 * @property-read Totem $totem
 */
class DeviceCredential extends Model
{
    protected $table = 'credenciais_dispositivo';

    protected $fillable = ['totem_id', 'nome', 'hash_token', 'ultimo_contato_em', 'revogado_em'];

    protected function casts(): array
    {
        return ['ultimo_contato_em' => 'datetime', 'revogado_em' => 'datetime'];
    }

    public function totem(): BelongsTo
    {
        return $this->belongsTo(Totem::class);
    }
}
