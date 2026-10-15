# TotemSync

Sistema de gestão e publicação de conteúdo para totens culturais. A aplicação
inclui editor visual, cadastro e monitoramento de equipamentos, exibição pública,
registro de interações, dashboard e relatórios.

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
npm run build
```

Configure as credenciais do MySQL no `.env` antes de executar as migrações.

O sistema não possui cadastro público. Crie o primeiro usuário por um seeder,
por um comando administrativo controlado ou com o Tinker no ambiente local.

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

## Dados de demonstração

Em ambiente local, crie ou atualize os dados fictícios com:

```bash
composer demo
```

O cenário inclui páginas publicadas, rascunho e página inativa, totens online e
offline e duas semanas de interações para preencher o dashboard e os
relatórios. O comando é idempotente e pode ser repetido sem duplicar o cenário.

- Login: `demo@editorcultura.test`
- Senha: `password`

O seeder possui bloqueio explícito para o ambiente de produção.

## Operação dos totens

O perfil padrão do TotemSync é um tablet Android na horizontal. Para preparar
o primeiro equipamento:

1. Execute `php artisan migrate` e `npm run build` após atualizar o projeto.
2. Em **Totens**, cadastre ou edite o equipamento e escolha sua página inicial,
   orientação **Horizontal** e o tempo de retorno por inatividade (padrão: 2 minutos).
3. Salve e clique no ícone **Preparar tablet** na linha do equipamento.
4. Copie o endereço gerado e abra-o no navegador do tablet em até 30 minutos.
   O endereço associa a sessão ao totem e habilita o monitoramento e as interações.
5. Configure o Android na horizontal e use o botão de tela cheia na exibição.

A configuração é aplicada na próxima abertura/navegação da exibição. Se a página
inicial escolhida for desativada ou excluída, o sistema usa uma página inicial
ativa; na ausência dela, usa a primeira página ativa. Sem páginas ativas, não é
possível gerar o endereço de inicialização.

O editor identifica o layout-base como **Tablet horizontal · 1366 × 768**. A
exibição ajusta esse layout à área disponível sem cortar o conteúdo e reserva
uma barra para **Voltar**, **Início** e **Próximo**. O perfil vertical continua
disponível para compatibilidade. A prévia sem associação a um totem não tem
retorno automático por inatividade.

O tablet precisa alcançar o servidor pelo mesmo endereço usado na geração do
link. Um domínio local do Laragon não é automaticamente resolvido pelo Android;
use um endereço de rede/domínio acessível e HTTPS na implantação. Não renomeie
o banco existente nem a pasta do projeto para alterar a identidade visual.

Ao atualizar uma instalação existente para `APP_NAME=TotemSync`, mantenha em
`SESSION_COOKIE` o nome do cookie anterior (nesta instalação,
`editorcultura-session`) para preservar logins e associações dos totens. As novas
instalações usam `totemsync-session` pelo `.env.example`.

Esta primeira etapa ainda usa a associação por sessão existente. Pareamento
persistente por código/QR, cache offline, sincronização de publicações e gestão
do Android em modo quiosque serão implementados nas etapas seguintes. Tela cheia
do navegador não bloqueia a saída para outros aplicativos.

As páginas públicas ficam em `totem/exibir/{id}`. Páginas marcadas como inativas
não são exibidas.

## Editor visual

O editor de páginas usa GrapesJS `0.23.6` e mantém dois estados separados:

- **rascunho**, salvo automaticamente e também pelo botão **Salvar rascunho**;
- **publicado**, atualizado somente após a confirmação no botão **Publicar**.

O documento editável é armazenado como JSON. Na publicação, o servidor valida o
projeto e sanitiza o HTML e o CSS antes de disponibilizá-los no totem. Scripts,
iframes, formulários, eventos JavaScript e recursos CSS externos não são aceitos.
Imagens são limitadas a JPG, PNG, WebP ou GIF com até 10 MB.

Páginas que ainda não foram publicadas pelo novo editor continuam exibindo o
conteúdo legado, evitando uma troca acidental durante a migração.

## Produção

Antes de publicar:

- use `APP_ENV=production` e `APP_DEBUG=false`;
- configure `APP_URL`, banco, e-mail e HTTPS;
- execute `php artisan optimize`;
- execute `npm run build`;
- não execute seeders de demonstração;
- mantenha escrita restrita às pastas `storage`, `bootstrap/cache` e ao diretório
  de uploads utilizado pelo editor.
