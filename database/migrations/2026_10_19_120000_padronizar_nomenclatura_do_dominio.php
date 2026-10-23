<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'page_revisions' => 'revisoes_pagina',
        'review_requests' => 'solicitacoes_revisao',
        'media_assets' => 'midias',
        'media_references' => 'referencias_midia',
        'audit_logs' => 'registros_auditoria',
        'device_credentials' => 'credenciais_dispositivo',
        'pairing_codes' => 'codigos_pareamento',
        'daily_metrics' => 'metricas_diarias',
    ];

    // IDs, timestamps e campos internos de autenticação/notificações seguem o Laravel.
    private const COLUMNS = [
        'users' => [
            'role' => 'perfil', 'is_active' => 'ativo', 'last_login_at' => 'ultimo_acesso_em',
            'session_version' => 'versao_sessao', 'birth_date' => 'data_nascimento',
            'must_change_password' => 'troca_senha_obrigatoria',
        ],
        'paginas' => [
            'editor_project_data' => 'pagina_dados_editor', 'published_html' => 'pagina_html_publicado',
            'published_css' => 'pagina_css_publicado', 'editor_version' => 'pagina_versao_editor',
            'published_at' => 'pagina_publicada_em', 'draft_version' => 'pagina_versao_rascunho',
            'publication_version' => 'pagina_versao_publicacao', 'workflow_status' => 'pagina_estado_editorial',
            'published_revision_id' => 'revisao_publicada_id', 'legacy_validated_at' => 'pagina_legado_validado_em',
        ],
        'totens' => ['reload_requested_at' => 'totem_recarga_solicitada_em'],
        'interacoes_totem' => ['event_uuid' => 'evento_uuid'],
        'page_revisions' => [
            'created_by' => 'criado_por', 'version' => 'versao', 'state' => 'estado',
            'project_data' => 'dados_projeto', 'checksum' => 'hash_verificacao', 'note' => 'observacao',
        ],
        'review_requests' => [
            'page_revision_id' => 'revisao_pagina_id', 'submitted_by' => 'enviado_por',
            'reviewed_by' => 'revisado_por', 'status' => 'situacao',
            'review_comment' => 'comentario_revisao', 'submitted_at' => 'enviado_em', 'reviewed_at' => 'revisado_em',
        ],
        'media_assets' => [
            'uploaded_by' => 'enviado_por', 'disk' => 'disco', 'path' => 'caminho',
            'original_name' => 'nome_original', 'mime_type' => 'tipo_mime', 'size' => 'tamanho',
            'checksum' => 'hash_verificacao', 'kind' => 'tipo', 'metadata' => 'metadados',
            'last_referenced_at' => 'ultima_referencia_em',
        ],
        'media_references' => ['media_asset_id' => 'midia_id'],
        'audit_logs' => [
            'user_id' => 'usuario_id', 'action' => 'acao', 'auditable_type' => 'auditavel_tipo',
            'auditable_id' => 'auditavel_id', 'ip_address' => 'endereco_ip',
            'user_agent' => 'agente_usuario', 'metadata' => 'metadados',
        ],
        'device_credentials' => [
            'name' => 'nome', 'token_hash' => 'hash_token',
            'last_seen_at' => 'ultimo_contato_em', 'revoked_at' => 'revogado_em',
        ],
        'pairing_codes' => [
            'created_by' => 'criado_por', 'code_hash' => 'hash_codigo',
            'expires_at' => 'expira_em', 'used_at' => 'usado_em',
        ],
        'daily_metrics' => [
            'metric_date' => 'data_metrica', 'event_count' => 'quantidade_eventos',
            'duration_seconds' => 'duracao_segundos', 'aggregation_key' => 'chave_agregacao',
        ],
    ];

    public function up(): void
    {
        $this->renameSchema(false);
    }

    public function down(): void
    {
        $this->renameSchema(true);
    }

    private function renameSchema(bool $reverse): void
    {
        $plans = [];
        // Valida tudo antes do primeiro DDL: MySQL não reverte renomeações por transação.
        foreach (self::COLUMNS as $original => $columns) {
            $translated = self::TABLES[$original] ?? $original;
            $source = $reverse ? $translated : $original;
            $target = $reverse ? $original : $translated;
            if ($source !== $target && Schema::hasTable($source) && Schema::hasTable($target)) {
                throw new RuntimeException("As tabelas {$source} e {$target} coexistem. Resolva o conflito antes de migrar.");
            }
            $table = Schema::hasTable($source) ? $source : $target;
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Tabela ausente: {$source}.");
            }
            $renames = [];
            $existing = Schema::getColumnListing($table);
            foreach ($reverse ? array_flip($columns) : $columns as $from => $to) {
                $hasSource = in_array($from, $existing, true);
                $hasTarget = in_array($to, $existing, true);
                if ($hasSource === $hasTarget) {
                    throw new RuntimeException("Colunas ausentes ou conflitantes: {$table}.{$from} / {$to}.");
                }
                if ($hasSource) {
                    $renames[$from] = $to;
                }
            }
            $plans[] = [$table, $target, $renames];
        }

        foreach ($plans as [$table, $target, $renames]) {
            foreach ($renames as $from => $to) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn($from, $to));
            }
            if ($table !== $target) {
                Schema::rename($table, $target);
            }
        }
    }
};
