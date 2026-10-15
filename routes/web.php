<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConteudoController;
// Controllers
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InteracaoTotemController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\TotemController;
use App\Http\Controllers\TotemPublicController;
use App\Http\Controllers\VisualEditorController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ROTAS DE AUTENTICAÇÃO
|--------------------------------------------------------------------------
|
| Login, logout, recuperação de senha e demais rotas
| disponibilizadas pelo Laravel Auth.
|
*/

// O sistema e administrativo: novas contas devem ser provisionadas
// de forma controlada, nunca por cadastro publico.
Auth::routes(['register' => false]);

/*
|--------------------------------------------------------------------------
| ROTAS PÚBLICAS
|--------------------------------------------------------------------------
|
| Páginas que não exigem autenticação.
|
*/

// Entrada da aplicacao.
Route::get('/', function () {
    return redirect()->route(
        Auth::check() ? 'admin' : 'login'
    );
})->name('inicio');

// Compatibilidade com o redirecionamento padrao do Laravel UI.
Route::get('/home', [
    HomeController::class,
    'index',
])->name('home');

// Exibição pública do conteúdo nos totens
Route::get('/totem/exibir/{id}', [
    TotemPublicController::class,
    'exibir',
])->whereNumber('id')
    ->name('totem.exibir');

// Registra as interações da exibição pública.
Route::post('/totem/interacoes', [
    InteracaoTotemController::class,
    'store',
])
    ->middleware('throttle:300,1')
    ->name('totem.interacoes.store');

// Mantem o status do equipamento associado a sessao atualizado.
Route::post('/totem/ping', [
    InteracaoTotemController::class,
    'ping',
])
    ->middleware('throttle:10,1')
    ->name('totem.ping');

// Associa um navegador de totem a um equipamento.
// A URL precisa ter uma assinatura válida do Laravel.
Route::get('/totem/iniciar/{totem}/{pagina}', [
    TotemPublicController::class,
    'iniciar',
])
    ->middleware('signed')
    ->name('totem.iniciar');

/*
|--------------------------------------------------------------------------
| ROTAS ADMINISTRATIVAS
|--------------------------------------------------------------------------
|
| Todas as rotas deste grupo exigem autenticação.
|
*/

Route::middleware(['auth'])->group(function () {

    /*
|--------------------------------------------------------------------------
| DASHBOARD ADMINISTRATIVO
|--------------------------------------------------------------------------
*/

    Route::get('/admin', [
        AdminController::class,
        'index',
    ])->name('admin');

    /*
    |--------------------------------------------------------------------------
    | GERENCIAMENTO DE CONTEÚDOS
    |--------------------------------------------------------------------------
    */

    Route::prefix('gestao-conteudo')->group(function () {

        Route::post('/editor/upload', [
            VisualEditorController::class,
            'upload',
        ])->name('editor-visual.upload');

        Route::put('/{pagina}/editor', [
            VisualEditorController::class,
            'save',
        ])->whereNumber('pagina')
            ->name('editor-visual.save');

        Route::post('/{pagina}/publicar-editor', [
            VisualEditorController::class,
            'publish',
        ])->whereNumber('pagina')
            ->name('editor-visual.publish');

        // Salvar conteúdo individual
        Route::post('/salvar', [
            ConteudoController::class,
            'store',
        ])->name('conteudos.store');

        // Salvar múltiplos blocos do editor
        Route::post('/salvar-massa', [
            ConteudoController::class,
            'salvarEmMassa',
        ])->name('conteudos.salvar-em-massa');

        // Upload de imagens
        Route::post('/upload-imagem', [
            ConteudoController::class,
            'uploadImagem',
        ])->name('conteudos.upload-imagem');

        // Publicar conteúdo
        Route::post('/publicar', [
            ConteudoController::class,
            'publicar',
        ])->name('conteudos.publicar');
    });

    /*
    |--------------------------------------------------------------------------
    | GERENCIAMENTO DE PÁGINAS
    |--------------------------------------------------------------------------
    |
    | Expõe somente as operações implementadas pelo controlador.
    */

    Route::resource(
        'gestao-conteudo',
        PaginaController::class
    )->only([
        'index',
        'store',
        'edit',
        'update',
        'destroy',
    ])->parameters([
        'gestao-conteudo' => 'gestao_conteudo',
    ]);

    /*
|--------------------------------------------------------------------------
| MONITORAMENTO E CADASTRO DE TOTENS
|--------------------------------------------------------------------------
*/

    Route::prefix('totens')
        ->name('admin.totens.')
        ->group(function () {

            // Listar totens
            Route::get('/', [
                TotemController::class,
                'index',
            ])->name('index');

            // Cadastrar
            Route::post('/', [
                TotemController::class,
                'store',
            ])->name('store');

            // Dados leves usados pela atualizacao automatica da tela.
            Route::get('/monitoramento/tempo-real', [
                TotemController::class,
                'status',
            ])->name('status');

            // Buscar os dados atuais do totem (JSON)
            Route::get('/{totem}/acesso', [
                TotemController::class,
                'acesso',
            ])->whereNumber('totem')->name('acesso');

            Route::get('/{totem}', [
                TotemController::class,
                'show',
            ])->whereNumber('totem')
                ->name('show');

            // Atualizar
            Route::put('/{totem}', [
                TotemController::class,
                'update',
            ])->whereNumber('totem')
                ->name('update');

            // Excluir
            Route::delete('/{totem}', [
                TotemController::class,
                'destroy',
            ])->whereNumber('totem')
                ->name('destroy');
        });

    /*
|--------------------------------------------------------------------------
| RELATÓRIOS DE USO
|--------------------------------------------------------------------------
*/

    Route::get('/relatorios', [
        RelatorioController::class,
        'index',
    ])
        ->middleware('auth')
        ->name('relatorios');
});
