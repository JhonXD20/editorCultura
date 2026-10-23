# Nomenclatura do banco de dados

A migração `2026_10_19_120000_padronizar_nomenclatura_do_dominio` padroniza em português os nomes das tabelas e colunas do domínio. Os nomes usam `snake_case`, sem acentos. As tabelas originais `paginas`, `totens`, `conteudos` e `interacoes_totem` permanecem com esses nomes.

As tabelas internas do Laravel (`users`, `sessions`, `password_reset_tokens`, `notifications`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` e `migrations`) mantêm os nomes originais. Em `users`, somente as colunas acrescentadas pelas funcionalidades do domínio são traduzidas. Os campos internos de autenticação, `id`, `created_at`, `updated_at`, `uuid`, `html` e `css` conservam suas convenções técnicas.

## Tabelas renomeadas

| Nome anterior | Nome atual |
| --- | --- |
| `page_revisions` | `revisoes_pagina` |
| `review_requests` | `solicitacoes_revisao` |
| `media_assets` | `midias` |
| `media_references` | `referencias_midia` |
| `audit_logs` | `registros_auditoria` |
| `device_credentials` | `credenciais_dispositivo` |
| `pairing_codes` | `codigos_pareamento` |
| `daily_metrics` | `metricas_diarias` |

## Colunas renomeadas

| Tabela atual | Nome anterior → nome atual |
| --- | --- |
| `users` | `role` → `perfil`; `is_active` → `ativo`; `last_login_at` → `ultimo_acesso_em`; `session_version` → `versao_sessao`; `birth_date` → `data_nascimento`; `must_change_password` → `troca_senha_obrigatoria` |
| `paginas` | `editor_project_data` → `pagina_dados_editor`; `published_html` → `pagina_html_publicado`; `published_css` → `pagina_css_publicado`; `editor_version` → `pagina_versao_editor`; `published_at` → `pagina_publicada_em`; `draft_version` → `pagina_versao_rascunho`; `publication_version` → `pagina_versao_publicacao`; `workflow_status` → `pagina_estado_editorial`; `published_revision_id` → `revisao_publicada_id`; `legacy_validated_at` → `pagina_legado_validado_em` |
| `totens` | `reload_requested_at` → `totem_recarga_solicitada_em` |
| `interacoes_totem` | `event_uuid` → `evento_uuid` |
| `revisoes_pagina` | `created_by` → `criado_por`; `version` → `versao`; `state` → `estado`; `project_data` → `dados_projeto`; `checksum` → `hash_verificacao`; `note` → `observacao` |
| `solicitacoes_revisao` | `page_revision_id` → `revisao_pagina_id`; `submitted_by` → `enviado_por`; `reviewed_by` → `revisado_por`; `status` → `situacao`; `review_comment` → `comentario_revisao`; `submitted_at` → `enviado_em`; `reviewed_at` → `revisado_em` |
| `midias` | `uploaded_by` → `enviado_por`; `disk` → `disco`; `path` → `caminho`; `original_name` → `nome_original`; `mime_type` → `tipo_mime`; `size` → `tamanho`; `checksum` → `hash_verificacao`; `kind` → `tipo`; `metadata` → `metadados`; `last_referenced_at` → `ultima_referencia_em` |
| `referencias_midia` | `media_asset_id` → `midia_id` |
| `registros_auditoria` | `user_id` → `usuario_id`; `action` → `acao`; `auditable_type` → `auditavel_tipo`; `auditable_id` → `auditavel_id`; `ip_address` → `endereco_ip`; `user_agent` → `agente_usuario`; `metadata` → `metadados` |
| `credenciais_dispositivo` | `name` → `nome`; `token_hash` → `hash_token`; `last_seen_at` → `ultimo_contato_em`; `revoked_at` → `revogado_em` |
| `codigos_pareamento` | `created_by` → `criado_por`; `code_hash` → `hash_codigo`; `expires_at` → `expira_em`; `used_at` → `usado_em` |
| `metricas_diarias` | `metric_date` → `data_metrica`; `event_count` → `quantidade_eventos`; `duration_seconds` → `duracao_segundos`; `aggregation_key` → `chave_agregacao` |

## Atualização de uma instalação existente

Faça backup do banco antes da atualização e interrompa os workers e o scheduler durante a troca de versão. Com o código novo disponível, execute:

```sh
php artisan down
php artisan migrate --force
php artisan config:clear
php artisan view:clear
php artisan up
```

Reinicie os workers e o scheduler com a versão nova do código. Não use `migrate:fresh` em bancos com dados a preservar. As migrações históricas continuam criando os nomes antigos; a migração final faz a tradução, tanto em instalações novas quanto nas existentes.

A migração usa renomeações, sem recriar registros ou alterar IDs, senhas, conteúdos, arquivos, valores de estados ou datas. As chaves estrangeiras e índices são preservados. Antes da primeira alteração, verifica nomes conflitantes ou ausentes; uma execução interrompida pode ser retomada. A reversão da última migração restaura os nomes antigos e deve ser acompanhada pela versão correspondente do código.

Os formulários de usuários usam `perfil`, `ativo` e `data_nascimento`. O contrato JSON do editor e do player mantém os campos existentes, como `project_data`, `draft_version`, `publication_version`, `published_at`, `version`, `checksum` e `reload_requested_at`, com conversão explícita para as colunas do banco.

## Verificação

`DomainSchemaMigrationTest` cobre dados preenchidos, reversão e reaplicação, relacionamentos, exclusões em cascata, chaves anuláveis, detecção de conflito antes de qualquer DDL e retomada de migração interrompida. A suíte funcional cobre autenticação, contas, revisão/publicação, biblioteca de mídia, pareamento e telemetria usando os novos nomes.
