# Carga cultural do TotemSync

## Recriação local

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

`migrate:fresh` apaga os dados do banco configurado. A carga de desenvolvimento
é permitida somente em `local` e `testing`; todos os seeders aplicam essa restrição.
`DatabaseSeeder` executa, em transação, `UserSeeder`, `CulturalMediaSeeder`,
`CulturalContentSeeder` e `TotemSeeder`, nessa ordem. `DemoSeeder` é apenas a
entrada compatível com `composer demo` e chama a mesma orquestração.

As migrations existentes foram mantidas. O ciclo entre página e revisão é
resolvido criando a página, depois sua revisão e, finalmente, a referência à
revisão publicada. Nenhum ID de página ou de usuário é fixado no código.

Repetir `php artisan db:seed` não redefine senhas, não sobrescreve páginas
editadas, não duplica revisões nem desfaz pareamentos. Em um banco já usado,
registros adicionais são preservados; para voltar ao conjunto exato, use o reset.

## Credenciais locais

| E-mail | Senha inicial | Perfil |
| --- | --- | --- |
| demo@editorcultura.test | password | admin — administrador de teste preservado |
| helena.martins@totemsync.test | Memorial@2026 | admin — administração institucional |
| rafael.nunes@totemsync.test | Curadoria@2026 | editor — curadoria histórica |
| marina.alves@totemsync.test | Patrimonio@2026 | editor — patrimônio e tradições |

Os nomes e contas adicionais são fictícios. As senhas são armazenadas com hash
pelo model `User`. As contas nascem ativas, verificadas e sem convite pendente,
para que o login local funcione imediatamente. O papel de curador usa o perfil
`editor`, já reconhecido pelas permissões do sistema.

## Percursos publicados

Página inicial: **Tocantins: caminhos de memória**, com dois cartões que abrem
as seções por `data-section-id`. A navegação continua usando o player existente.

| Seção | Ordem | Página |
| --- | --- | --- |
| Criação & Memória do Tocantins | 1 | A Luta pela Emancipação |
| Criação & Memória do Tocantins | 2 | O Memorial e os Heróis |
| Criação & Memória do Tocantins | 3 | Marcos Históricos & Geográficos |
| Patrimônio Vivo & Tradições Culturais | 1 | Centro Histórico de Porto Nacional |
| Patrimônio Vivo & Tradições Culturais | 2 | Festas Populares & Tradições |
| Patrimônio Vivo & Tradições Culturais | 3 | Identidade do Cerrado, Arte & Saberes |

Cada página contém título, resumo, imagem legendada, três blocos de leitura,
pergunta para reflexão e referências. Os textos ficam em
`database/seeders/content/cultural.php`. O layout e o HTML derivam da mesma árvore
de componentes em `database/seeders/Support/CulturalPageLayout.php`; as regras
de estilo também são compartilhadas entre o projeto GrapesJS e o player.

O conteúdo é apresentado em **telas fixas de 1366 × 768, sem rolagem horizontal
ou vertical**. Cabeçalho, imagem lateral, três blocos curtos e rodapé cabem no
mesmo quadro. O player escala o quadro completo à área do equipamento. Conteúdo
adicional deve ser resumido ou dividido em outras telas, mantendo a leitura
legível; esconder texto excedente não é uma solução válida. Os testes de navegador
conferem os limites de todos os elementos das sete telas, inclusive offline.

As sete páginas são ativas, estado `published`, versão de publicação 1, com uma
revisão imutável e checksum. HTML, CSS e projeto passam pelos validadores atuais.
As seis páginas internas têm associação explícita à seção e ordem 1–3.
O manifesto do player inclui todas as páginas, seções e imagens para sincronização.

Foram reaproveitadas quatro imagens do acervo existente: Catedral e casario
(3216 × 2136), painel da CENOG (1000 × 500), orla (1024 × 530) e casarão
(1758 × 629). Os seeders copiam os originais para `storage/app/public/media/cultural`,
catalogam dimensões, procedência e SHA-256 na biblioteca e registram referências
por página. Não há dependência de serviços externos para exibir o conteúdo.
As fotos ilustrativas da cidade não são apresentadas como registros das festas.

Referências editoriais incluem o
[Iphan sobre Porto Nacional](https://www.gov.br/iphan/pt-br/patrimonio-cultural/patrimonio-material/bens-tombados/conjuntos-urbanos-tombados-cidades-historicas/norte/porto-nacional-to),
o [TCE/TO sobre a memória do Estado](https://www.tceto.tc.br/wp-content/uploads/2021/08/revista-hist-tce-web.pdf)
e a [Prefeitura de Mateiros sobre Mumbuca](https://www.mateiros.to.gov.br/noticia/colheita-do-capim-dourado-reune-tradicao-secular-do-mumbuca).
As referências específicas são identificadas no rodapé; os endereços completos
permanecem no arquivo de conteúdo cultural.

## Equipamentos e conexão

1. **Totem 01 – Entrada & Recepção (Hall Principal)**.
2. **Totem 02 – Galeria Histórica (Pavilhão Central)**.
3. **Totem 03 – Sala Multimídia (Espaço Memória & Tradições)**.

Os IDs são distintos, atribuídos pelo banco. Todos apontam para a página inicial
publicada, com orientação horizontal e retorno por inatividade de 120 segundos.
O schema atual não tem campo `ativo` em `totens`: o monitoramento online/offline
é calculado a partir de `totem_ping`. Por isso não há heartbeats inventados;
os três equipamentos estão disponíveis para parear e aparecem online após conectar.
Também não são geradas interações ou avaliações fictícias.

Abra `/pairing` no navegador do equipamento, entre como administrador e selecione
o totem. O fluxo encerra a sessão administrativa e grava a credencial do player
em cookie HttpOnly. Os links `/parear/{codigo}` exibidos durante o seed são uma
alternativa com validade de dez minutos e uso único. É possível gerar outro código
em **Totens → Preparar tablet**. O token persistente é emitido no pareamento e
armazenado no banco somente como SHA-256, conforme a autenticação já existente.

`storage:link` só é necessário se `public/storage` ainda não existir. No Windows,
uma junction válida para `storage/app/public` cumpre o mesmo papel.

## Auditoria de 20/10/2026

A auditoria incluiu todos os seeders, as cinco factories, testes PHP/JavaScript/E2E,
uploads e o acervo de `public/totem/imagens-conteudo`. As factories foram mantidas:
elas atendem aos testes de domínio, autenticação, publicação, dispositivos e métricas.
Testes funcionais úteis e arquivos históricos únicos foram preservados, inclusive
retratos pequenos com valor documental. A resolução mínima das imagens escolhidas
para a carga é de 1000 pixels de largura.

Código retirado/substituído:

- `database/seeders/TotemInicialSeeder.php`: IDs fixos e conteúdo inicial legado.
- `database/seeders/InteracoesTotemSeeder.php`: geração de métricas fictícias.
- Antigas implementações de `TotemSeeder` e `DemoSeeder`: dispositivos e páginas
  genéricos substituídos pela estrutura cultural modular.
- `tests/Unit/ExampleTest.php`: verificava apenas `true === true`.
- `tests/Feature/DemoSeederTest.php`: cenário antigo substituído por
  `TotemSyncSeederTest`, cobrindo credenciais, publicação, ordem, mídias,
  idempotência, pareamento real, manifesto e heartbeat dos três dispositivos.
- `tests/Feature/ExampleTest.php` foi renomeado para `RootRedirectTest.php`,
  preservando a verificação do redirecionamento inicial.

Os arquivos de mídia retirados são detalhados em
[SEED-CULTURAL-ARQUIVOS.csv](SEED-CULTURAL-ARQUIVOS.csv), com motivo, SHA-256 e
caminho da cópia mantida quando aplicável. Cada arquivo foi copiado para backup
privado e teve seu hash conferido antes da remoção da pasta pública. Nenhuma
remoção recursiva foi usada. Caminhos referenciados pelo código ou pelo novo
conteúdo foram preservados.

Total: **109 arquivos**, aproximadamente **179,5 MiB**: 91 duplicatas binárias,
cinco temporários do Office e 13 uploads órfãos sem uso cultural na nova carga.
Os arquivos únicos do acervo histórico foram preservados.

Backups locais, ignorados pelo Git:

- Banco anterior: `storage/app/backups/local-20261020-121026-ebad9d/database.sql`.
- SHA-256 do dump: `c7ea84990af632a53de1b6f79b6312c955024607b515472c8c36e6a40f785fd5`.
- Mídias retiradas e manifesto: `storage/app/backups/cultural-cleanup-20261020/`.

Os testes de navegador usam banco SQLite separado. Sua preparação agora executa
`migrate:fresh --seed` nesse banco isolado, evitando acumular os cenários antigos.
O banco MySQL da aplicação não é usado pelos testes.

Validação concluída:

- `php artisan migrate:fresh --seed`: todas as 19 migrations executadas no MySQL
  local, sem erros de chaves estrangeiras; quatro contas, três totens e sete
  revisões publicadas conferidos diretamente no banco.
- Repetição de `php artisan db:seed`: contagens e versão dos manifestos preservadas.
- `public/storage`: junction existente conferida pelo SHA-256 das quatro imagens.
- PHP: 126 testes, 1152 assertions, todos aprovados (baseline: 124 testes).
- JavaScript: dez testes aprovados.
- Navegador: 33 cenários aprovados na validação inicial. Após a adequação às
  telas sem rolagem, os 12 cenários de editor/player foram executados novamente
  em desktop, tablet e celular, incluindo navegação offline e verificação de
  overflow/cortes em todos os elementos das sete telas.
- Pint e verificação de espaços no diff: aprovados nos arquivos alterados.
- Todas as 109 cópias de mídia em backup tiveram seus hashes revalidados.

A adequação ao formato sem rolagem foi aplicada às sete páginas do banco local,
criando sete novas revisões publicadas e preservando as revisões anteriores,
usuários, totens, pareamentos e métricas. O snapshot anterior está em
`storage/app/backups/cultural-fixed-screen-20261020-155651-d94333.json`.
O seed normal continua preservando páginas existentes. A atualização explícita
da coleção usa `CulturalContentSeeder::run(refreshExisting: true)` dentro de
uma transação, com backup prévio, sem exigir reset do banco.
