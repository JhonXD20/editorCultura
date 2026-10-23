# Modernização — estado da implementação

Atualizado em 20/10/2026. Preparação local; não há VPS nem domínio contratado e nenhum deploy externo foi realizado.

## Implementado

- UX assíncrona no painel, confirmações de mídia/perfil e ações críticas reautenticadas. Criação usa convite por e-mail; reset administrativo e recuperação pública usam links de uso único, válidos por 30 minutos. O usuário define a própria senha e as sessões são revogadas ao concluir; detalhes em [UX-AND-ACCOUNTS.md](UX-AND-ACCOUNTS.md). Configuração SMTP independente de provedor em `.env.smtp.example`; entrega real depende das credenciais de envio.
- Eventos com ocorrência e recebimento separados, horário do dispositivo limitado a uma janela de 90 dias até 5 minutos futuros, confirmação explícita de persistência/rejeição e relatórios que incluem agregados históricos. Questionário opcional por equipamento, fila offline e resultados agregados; veja [METRICAS-E-AVALIACOES.md](METRICAS-E-AVALIACOES.md).

- Papéis `admin`, `editor` e `pairing`, sem cadastro público; autorização no servidor, gestão de usuários e revogação de sessões por versão, inclusive com Redis.
- Rascunho com versão otimista (409), revisões de conteúdo imutáveis, submissão, aprovação do snapshot exato, devolução com comentário e restauração como nova revisão. Aprovar uma revisão não apaga um rascunho posterior.
- Notificações persistentes e apresentação AdminLTE; trilha de auditoria com bloqueio de alteração pelo modelo.
- Gerenciador com criação, edição de título, duplicação, ativação/desativação, escolha da inicial e exclusão administrativa. A inicial não pode ser desativada ou excluída antes de substituição.
- Seções com cadastro, renomeação, associação de páginas e ordenação por arraste/setas com controle de versão. Cards, imagens e textos podem abrir uma seção; o player resolve sua primeira página ativa e publicada e navega por áreas de toque, preservando shell, tela cheia e histórico. Estrutura, permissões e migração em [SECOES.md](SECOES.md).
- GrapesJS como único editor; blocos locais de vídeo, galeria, carrossel, cards, acordeão e modelo cultural, além dos blocos básicos. Tela inteira com navegação do app e painéis recolhíveis em telas pequenas.
- Ajustes separados por dispositivo; adaptação automática não sobrescreve propriedades responsivas já existentes.
- Biblioteca com UUID, MIME real, limites, SHA-256, dimensões de imagens e referências a rascunhos, publicação e revisões retidas. Exclusão de mídia referenciada bloqueada.
- Pareamento por código/QR de uso único (10 minutos), conta técnica restrita, token opaco persistente com hash no servidor e cookie HttpOnly. Logout humano ao parear; revogação e solicitação de recarga no painel.
- Manifesto estável por conteúdo/configuração/release; Service Worker com download em cache intermediário e troca do ponteiro somente após sincronização completa; última versão válida mantida.
- Polling de 30 segundos, heartbeat de 60 segundos, fila IndexedDB e UUID para deduplicação de telemetria. Consolidação diária anterior a 24 meses e poda programada de auditoria, revisões e órfãos.
- Docker multi-stage, Compose, Nginx/HTTPS, MySQL 8.4 e Redis; CI de qualidade/navegador, imagens por commit, deploy com aprovação de environment e scripts de rollback; backup Restic criptografado com retenção.

## Evidências locais

- Substituição do envio de senhas por links: 124 testes PHP (783 asserções), 10 JavaScript e 6 cenários de contas em navegador aprovados nos três tamanhos Chromium; Pint, PHPStan e build aprovados. Convite, recuperação, uso único, expiração, reemissão, falha de transporte e revogação cobertos com entrega simulada. A migração `2026_10_20_150000` foi aplicada após backup em `storage/app/backups/local-20261020-120232-41d963`; hashes dos campos anteriores de oito tabelas confirmaram preservação dos dados, incluindo as seis contas existentes. O envio externo continua pendente de SMTP; o `.env` não recebeu credenciais.

- Rodada anterior de e-mail, posteriormente substituída por convites: 112 testes PHP (708 asserções), 10 JavaScript e 6 cenários de contas em navegador aprovados; PHPStan, Pint, Composer Validate e build aprovados. A entrega foi simulada; o ambiente local permanece com log até configurar o SMTP.

- Rodada de 20/10/2026: 105 testes PHP (646 asserções), 10 JavaScript, PHPStan e Pint aprovados. Os 33 cenários funcionais Playwright passaram. Após a revisão visual, os três cenários de questionário passaram novamente e os nove cenários de seções foram aprovados na revisão do player. Encerramento automático do servidor E2E no Windows validado. Migrações locais aplicadas após dump e hashes dos dados anteriores de oito tabelas conferidos como preservados; nenhuma senha existente foi redefinida. Aceite físico e homologação de infraestrutura foram adiados pelo autor.

- Suítes PHP, análise estática, Pint, testes JavaScript e build são executáveis pelos comandos do README. Resultados da rodada final devem ser consultados junto ao relatório da entrega, não inferidos da existência dos arquivos.
- Rodada local de seções: 91 testes PHP (532 asserções), 10 testes JavaScript e 30 cenários Playwright aprovados nos três tamanhos Chromium; Pint e PHPStan sem erros, build concluído. Backup anterior à migração de seções restaurado em banco temporário: 22 tabelas e 1.830 registros verificados. Após aplicar a migração local, hashes das 8 tabelas de conteúdo/operação confirmaram a preservação dos dados anteriores.
- Rodada local após UX/contas: 76 testes PHP (412 asserções), 6 testes JavaScript e 21 cenários Playwright aprovados; Pint, Composer Validate e PHPStan sem erros. Build Vite concluído com aviso de tamanho do bundle GrapesJS (~302 KB gzip).
- Playwright cobre login/gerenciador/carregamento do editor e ações visíveis, edição real de texto com persistência e aviso AdminLTE, pareamento por cookie, navegação offline e acesso não pareado nos três tamanhos Chromium, com CSP habilitada. Também cobre navegação parcial/histórico preservando o shell, mídia com cancelamento/erro/exclusão, reset reautenticado, troca obrigatória, perfil e revogação. Não equivale a validar todas as combinações de blocos nem o Edge real.
- Backup original: `storage/app/backups/pre-modernizacao-20261015/repository.bundle` e `database.sql`, fora do Git. O bundle passou em `git bundle verify`.
- Dump restaurado em banco MySQL temporário independente: 13 tabelas verificadas com `CHECK TABLE`, 1.723 registros. SHA-256: `97abc13af10702fbe01183f1fc1debfa138801f33de9111f4ef6a8f11c1d647f`.
- 34 imagens históricas migradas para `storage/app/public/media/legacy`; todas conferidas por tamanho e SHA-256. Os originais públicos foram removidos; cópias originais continuam recuperáveis pelo bundle Git. URLs antigas redirecionam para a biblioteca.
- FontAwesome duplicado removido; a distribuição usada pelo AdminLTE foi preservada.

## Pendências para aceite integral

1. **Runtime Linux:** o Docker instalado localmente não tem daemon disponível. Compose foi validado sintaticamente, mas build/boot das imagens PHP 8.5, permissões dos volumes, health checks, Redis, fila, scheduler, backup e rollback ainda precisam passar em ambiente Linux isolado.
2. **Operação externa:** contratar VPS/domínio; configurar DNS, firewall/SSH por chave, credenciais e repositório Restic externo. Emitir e testar renovação do HTTPS; provar restauração do snapshot criptografado e configurar alertas de falha de backup.
3. **GitHub:** configurar secrets, revisores obrigatórios do environment `production` e proteção de branch. O YAML não configura essas políticas na conta. CI ainda precisa executar no GitHub; nenhum push ou deploy foi feito nesta entrega.
4. **Migração visual:** executar o conversor primeiro com `--dry-run`, comparar cada página nos três perfis e publicar apenas após revisão. O renderizador público legado e o modelo de conteúdo permanecem deliberadamente para evitar perda enquanto essa validação não terminar. O relatório de incompatibilidades ainda precisa ser ampliado além da contagem por página; `pagina_legado_validado_em` está reservado para essa etapa.
5. **Aceite ampliado:** testes de edição real de todos os blocos, upload de vídeos grandes, erros/rollback durante sincronização, publicação chegando em 30 segundos, reenvio offline, concorrência MySQL real e Edge instalado. A suíte atual não prova esses critérios integralmente.
6. **Endurecimento adicional:** auditoria é append-only na aplicação, não um armazenamento WORM contra um administrador do banco. A unicidade da página inicial agora também é garantida por coluna gerada com índice único; concorrência MySQL real continua no aceite ampliado.
7. **Histórico Git:** mídias saíram da árvore atual, mas não do histórico. Não houve reescrita/force-push. Realizar em clone separado, após backup externo e coordenação com todos os clones; não reescrever este checkout automaticamente.

## Limites conhecidos

- Offline exige HTTPS (ou localhost), armazenamento permitido no navegador e primeira sincronização completa. Limpeza do navegador/quota removem o cache; vídeos grandes precisam de teste no hardware real.
- Uma credencial revogada é rejeitada pelo servidor imediatamente. Um dispositivo desconectado só recebe a revogação ao reconectar; conteúdo já baixado não pode ser apagado remotamente sem conectividade.
- Sincronização atômica evita ativar download incompleto, mas ainda não valida SHA-256 de cada binário no cliente; o manifesto possui checksum das páginas e a biblioteca mantém checksum dos arquivos no servidor.
- `admin` pode publicar diretamente; `editor` precisa de aprovação administrativa. Não existe HTML livre nem execução de scripts inseridos pelo usuário. Não há paridade integral com Elementor.
- Há uma única organização. Android/MDM, alta disponibilidade, banco gerenciado e reinicialização física de dispositivos não estão incluídos.
