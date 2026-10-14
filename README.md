# Editor Cultura

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
php artisan test
```

No Laragon, caso as extensões SQLite estejam instaladas mas desabilitadas no
`php.ini`, a suíte pode ser executada sem alterar a configuração global:

```bash
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit
```

## Operação dos totens

Cada navegador de totem deve ser inicializado por uma URL assinada da rota
`totem.iniciar`. Essa URL associa a sessão ao equipamento, habilita o heartbeat
de monitoramento e vincula as interações ao totem correto.

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
