# TotemSync

Sistema de gestão e publicação de conteúdo para totens culturais. A aplicação
inclui editor visual, cadastro e monitoramento de equipamentos, exibição pública,
registro de interações, dashboard e relatórios.

Preparação local da modernização em andamento. Veja o [estado da implementação e pendências](docs/MODERNIZATION.md)
e o [runbook de produção Ubuntu/Docker](docs/PRODUCTION.md). Nenhuma VPS ou domínio foi configurado nesta etapa.

## Requisitos

- PHP 8.3 ou superior, com extensões MySQL, DOM e Fileinfo
- Composer 2
- Node.js e npm
- MySQL 8 ou MariaDB compatível

Para executar os testes também é necessário habilitar `pdo_sqlite` e `sqlite3`.

## Instalação

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
php artisan migrate
php artisan storage:link
npm run build
```

Configure as credenciais do MySQL no `.env` antes de executar as migrações.

As tabelas e colunas do domínio seguem a [nomenclatura em português](docs/NOMENCLATURA-BANCO.md).
Esse documento também descreve a atualização de bancos existentes e os nomes internos do Laravel preservados.

O sistema não possui cadastro público. Crie o primeiro usuário por um seeder,
por um comando administrativo controlado ou com o Tinker no ambiente local.

As demais contas são criadas em **Usuários**, com convite por e-mail para definir a própria senha. Convites e links de recuperação são de uso único e vencem em 30 minutos. A senha atual só muda após concluir a recuperação, que encerra as sessões anteriores. Configure um transporte de envio antes dessas ações: veja [SMTP independente de provedor](docs/EMAIL.md) e [contas e senhas](docs/UX-AND-ACCOUNTS.md).

## Desenvolvimento

```bash
composer run dev
```

O comando inicia o servidor Laravel, worker de fila, visualização de logs e Vite.

## Testes

```bash
composer test
```

O comando usa um banco SQLite temporário em memória e, no Laragon, carrega as
extensões necessárias sem alterar o `php.ini`. Nenhum dado do banco de
desenvolvimento é modificado.

A suíte cobre autenticação, temas, dashboard, gestão de páginas e conteúdos,
editor visual, segurança e sanitização, CRUD e monitoramento de totens,
telemetria, navegação pública, relatórios e o cenário demonstrativo.

Para validar também o temporizador de inatividade e a escolha de layout do tablet:

```bash
npm test
```

Para executar somente uma classe ou um teste:

```bash
composer test -- --filter=ReportTest
```

Verificações adicionais:

```sh
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress
npm test
npm run build
npx playwright install chromium
npm run test:e2e
```

Playwright inicia um servidor e banco SQLite separados; não usa o banco do `.env`.
Os testes usam os assets compilados, mesmo com o Vite de desenvolvimento aberto.
No CI também há banco MySQL e validação do PHP 8.5.

## Acervo cultural de desenvolvimento

Para recriar o banco local e carregar o acervo do Memorial:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

O reset apaga o banco configurado. A carga é restrita aos ambientes `local` e
`testing`: quatro usuários, exatamente três totens, duas seções com três páginas
cada e uma página inicial. As sete páginas possuem revisão publicada, projeto
GrapesJS, estilos e mídias locais catalogadas. O seed não depende de downloads.

O login habitual permanece `demo@editorcultura.test` / `password`, perfil `admin`.
As outras credenciais, a estrutura editorial e o relatório da limpeza estão em
[docs/SEED-CULTURAL.md](docs/SEED-CULTURAL.md).

Para repetir a carga sem apagar o banco, use `php artisan db:seed` ou `composer demo`.
Os seeders preservam contas, senhas alteradas, páginas existentes e pareamentos.
`DemoSeeder` mantém compatibilidade com scripts antigos e chama a mesma carga;
os cenários de telemetria fictícia foram retirados. O total exato de três totens
é garantido na instalação limpa, sem excluir dispositivos cadastrados depois.

Para conectar o Web Player, entre em `/pairing` com uma conta administradora e
selecione o equipamento. Também são exibidos códigos de uso único no terminal,
válidos por dez minutos. O pareamento emite o token persistente em cookie HttpOnly
e guarda somente seu hash no banco. Não há tokens compartilhados ou fixos no seed.
Os totens passam a aparecer online após o primeiro heartbeat real do player.

## Operação dos totens

O perfil padrão do TotemSync é um tablet Android na horizontal. Para preparar
o primeiro equipamento:

1. Execute `php artisan migrate` e `npm run build` após atualizar o projeto.
2. Em **Totens**, cadastre ou edite o equipamento e escolha sua página inicial,
   orientação **Horizontal** e o tempo de retorno por inatividade (padrão: 2 minutos).
3. Salve e clique no ícone **Preparar tablet** na linha do equipamento.
4. Abra o QR Code/endereço no navegador do tablet em até 10 minutos e confirme o pareamento.
   O código aceita um único uso e emite uma credencial persistente de dispositivo.
5. Configure o Android na horizontal e use o botão de tela cheia na exibição.

A configuração é aplicada na próxima abertura/navegação da exibição. Se a página
inicial escolhida for desativada ou excluída, o sistema usa uma página inicial
ativa; na ausência dela, usa a primeira página ativa. Sem páginas ativas, não é
possível gerar o endereço de inicialização.

O editor identifica o layout-base como **Tablet horizontal · 1366 × 768**. A
exibição ajusta esse layout à área disponível sem cortar o conteúdo. Nas seções,
o visitante toca à esquerda/direita para voltar/avançar; a barra mantém **Início**
e tela cheia. Páginas avulsas conservam **Voltar**, **Início** e **Próximo**. O perfil vertical continua
disponível para compatibilidade. A prévia sem associação a um totem não tem
retorno automático por inatividade.

As telas de conteúdo devem caber inteiras no quadro de **1366 × 768**, sem
rolagem horizontal ou vertical. Use textos curtos, imagens proporcionais e
navegação entre telas para conteúdos maiores. O acervo cultural usa esse formato;
seus testes verificam que todos os elementos cabem, sem texto cortado.

O tablet precisa alcançar o servidor pelo mesmo endereço usado na geração do
link. Um domínio local do Laragon não é automaticamente resolvido pelo Android;
use um endereço de rede/domínio acessível e HTTPS na implantação. Não renomeie
o banco existente nem a pasta do projeto para alterar a identidade visual.

O dispositivo usa cookie HttpOnly independente da sessão humana e o servidor guarda
somente o hash do token. Também existe uma conta técnica `pairing` para associação.
O painel permite revogação e solicitação de recarga. Aguarde a indicação de versão
disponível offline antes de desconectar o equipamento; HTTPS é obrigatório fora de localhost.
O player confere publicações a cada 30 segundos e mantém a última sincronização completa.
O questionário opcional pode ser habilitado por equipamento; as respostas e as regras de telemetria estão descritas em [Métricas e avaliações](docs/METRICAS-E-AVALIACOES.md).
Tela cheia não bloqueia a saída para outros aplicativos; Android/MDM não está incluído.

As páginas do player ficam em `totem/exibir/{id}` e exigem credencial. Páginas
inativas ou não publicadas não são exibidas. Prévia administrativa exige login.

## Editor visual

As páginas podem ser organizadas em seções com sequência de leitura e navegação por toque.
Veja [como cadastrar seções, ordenar páginas e configurar os cards de entrada](docs/SECOES.md).

O editor de páginas usa GrapesJS `0.23.6` e mantém dois estados separados:

- **rascunho**, salvo automaticamente e também pelo botão **Salvar rascunho**;
- **publicado**, atualizado por um administrador; editores enviam uma revisão imutável para aprovação.

O histórico permite restauração como novo rascunho. Devolução exige comentário;
conflitos entre versões retornam 409, sem sobrescrever a alteração de outro editor.
Os últimos 30 registros são preservados, além de todos os publicados e pendentes.

O documento editável é armazenado como JSON. Na publicação, o servidor valida o
projeto e sanitiza o HTML e o CSS antes de disponibilizá-los no totem. Scripts,
iframes, formulários, eventos JavaScript e recursos CSS externos não são aceitos.
Imagens são limitadas a JPG, PNG, WebP ou GIF com até 10 MB.
Vídeos locais MP4/WebM de até 200 MB são enviados pela biblioteca de mídia.
Arquivos em uso por páginas ou revisões não podem ser excluídos.

Páginas que ainda não foram publicadas pelo novo editor continuam exibindo o
conteúdo legado, evitando uma troca acidental durante a migração.

## Projeto de TCC

O [projeto de TCC atualizado](docs/tcc/Projeto-TCC-TotemSync.md) e o
[relatório técnico de 23/10/2026](docs/tcc/Relatorio-Revisao-2026-10-23.md)
relacionam implementação, testes e pendências de avaliação. Os materiais de
`docs/tcc` são locais e ignorados pelo Git; precisam acompanhar a entrega
acadêmica separadamente.

## Produção

Alvo: Ubuntu 24.04 LTS, Docker Compose, PHP 8.5-FPM, Nginx com HTTPS,
MySQL 8.4 e Redis. Laragon fica apenas no desenvolvimento.

Consulte [docs/PRODUCTION.md](docs/PRODUCTION.md) antes de configurar infraestrutura,
segredos, certificados, CI, backup ou rollback. A presença da configuração Docker
não significa que o ambiente já foi homologado: veja as pendências em
[docs/MODERNIZATION.md](docs/MODERNIZATION.md).
