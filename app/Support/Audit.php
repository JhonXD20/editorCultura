<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class Audit
{
    public static function record(string $action, ?Model $subject = null, array $metadata = []): void
    {
        AuditLog::create([
            'usuario_id' => Auth::id(),
            'acao' => $action,
            'auditavel_tipo' => $subject?->getMorphClass(),
            'auditavel_id' => $subject?->getKey(),
            'endereco_ip' => request()->ip(),
            'agente_usuario' => mb_substr((string) request()->userAgent(), 0, 500),
            'metadados' => $metadata,
        ]);
    }
}
