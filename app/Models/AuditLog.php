<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    protected $table = 'registros_auditoria';

    public const UPDATED_AT = null;

    protected $fillable = ['usuario_id', 'acao', 'auditavel_tipo', 'auditavel_id', 'endereco_ip', 'agente_usuario', 'metadados'];

    protected function casts(): array
    {
        return ['metadados' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Registros de auditoria são imutáveis.'));
        static::deleting(fn () => throw new LogicException('Registros de auditoria são imutáveis.'));
    }
}
