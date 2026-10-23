# Métricas e avaliações

Atualizado em 20/10/2026. As alterações foram preparadas e verificadas localmente. Aceite no equipamento físico e homologação de infraestrutura permanecem para uma etapa posterior.

## Ocorrência e recebimento

O player envia `occurred_at` com o evento e mantém esse valor durante as tentativas offline. O servidor armazena `ocorrido_em` no fuso da aplicação e preserva `created_at` como instante do recebimento. Dashboard, relatórios e consolidação usam `COALESCE(ocorrido_em, created_at)` para manter a compatibilidade com o histórico.

Datas válidas entre 90 dias atrás e 5 minutos à frente do recebimento são aceitas e marcadas com `horario_validado=true`. Fora dessa janela, ou em clientes antigos sem horário, o servidor usa o recebimento com indicador falso. Essa validação limita datas incoerentes; não certifica que o relógio físico esteja correto. Os horários originais de eventos antigos não podem ser recuperados retroativamente.

`acknowledged` contém somente eventos persistidos ou duplicatas já persistidas para o mesmo equipamento. Uma página publicada que foi desativada continua aceitando seu histórico offline. Páginas excluídas/nunca publicadas são rejeitadas explicitamente com `page_unavailable`; UUID de outro equipamento é rejeitado com `uuid_conflict`. Rejeições permanentes retornam `retryable=false`: a fila deixa de repetir e o player informa a rejeição. Falhas de rede/servidor mantêm os registros para nova tentativa. Eventos não são transferidos entre credenciais de equipamentos.

## Histórico consolidado

Eventos anteriores a 24 meses são consolidados por data, equipamento, página e tipo, e removidos dos registros individuais na mesma transação. Relatórios somam os dados brutos e os agregados sem sobreposição normal do processo. A média de permanência usa soma das durações dividida pelo número de amostras; não usa média de médias.

O agregado diário não conserva a hora. Quando o período inclui toques consolidados, o relatório mantém totais/gráficos diários, informa a limitação e não apresenta um pico horário incompleto como se representasse todo o intervalo.

Toques não representam visitantes únicos. Duração mede o período em que a página ficou visível, não atenção ou aprendizado. Os registros de permanência podem ser segmentados por navegação e visibilidade do navegador.

## Avaliação opcional

Em **Totens → Cadastrar/Editar**, a opção **Disponibilizar avaliação opcional aos visitantes** habilita cinco afirmações sobre a experiência. Ela começa desativada em todos os equipamentos existentes e novos. A configuração integra a versão do manifesto e chega ao player pela sincronização.

O visitante pode pular, sair com Escape, deixar questões sem resposta ou escolher NA. Para enviar precisa marcar participação voluntária e responder pelo menos uma questão, incluindo NA. As perguntas e a versão do instrumento estão em `App\Support\VisitorQuestionnaire`. A coleta não inclui nome, contato, IP ou texto livre; respostas são associadas ao equipamento, sem identificação do visitante.

O formulário é esvaziado ao sair e fechado após dois minutos sem interação. Respostas confirmadas localmente ficam no IndexedDB durante indisponibilidade da rede. O reenvio é idempotente por UUID; a fila é separada por credencial. O servidor precisa continuar com avaliação habilitada para receber as respostas pendentes. Se for desabilitada antes do reenvio, a fila permanece no navegador; não é marcada como enviada. Limpeza/quota do navegador pode remover filas locais.

**Avaliações**, no painel administrativo, apresenta a distribuição por questão e equipamento, separando NA e questões puladas. O acesso é exclusivo de administradores. O scheduler remove respostas recebidas há mais de 24 meses. Alteração das perguntas exige nova versão e uma decisão explícita sobre os relatórios das versões anteriores.

A funcionalidade pode ser ensaiada com dados de teste; instrumento e procedimento de aplicação devem ser definidos antes de uma coleta com visitantes. Nenhuma pesquisa ou coleta real foi executada nesta implementação.

## Verificações

Os testes de regressão cobrem ocorrência no dia anterior, data incoerente, lote inválido, duplicação, página desativada e rejeição explícita. Relatórios são comparados antes/depois da consolidação, incluindo distribuição e média ponderada. Os testes de contas cobrem segredo aleatório, expiração, troca obrigatória, reset e ausência do segredo na auditoria.

A suíte de navegador inclui senha temporária exibida uma vez e questionário opcional com envio após desconexão, nos perfis Chromium desktop, tablet e celular. Emulação não equivale a homologação do equipamento físico.

## Arquivos acadêmicos locais

Documentos, capturas, conteúdos demonstrativos e utilitários específicos de apresentação acadêmica são ignorados pelo Git e excluídos do contexto Docker. A documentação técnica e o código genérico da aplicação continuam versionáveis. Arquivos ignorados continuam no computador; não fazem parte de um clone limpo do repositório.
