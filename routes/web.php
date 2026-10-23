<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AccessLinkController;
use App\Http\Controllers\Auth\AccessPasswordController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SecaoController;
use App\Http\Controllers\TotemController;
use App\Http\Controllers\TotemPublicController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisualEditorController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Auth::routes(['register' => false, 'reset' => false]);
Route::get('/password/reset', [AccessLinkController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [AccessLinkController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');
Route::get('/password/reset/{token}', [AccessPasswordController::class, 'showResetForm'])->middleware('throttle:30,1')->name('password.reset');
Route::post('/password/reset', [AccessPasswordController::class, 'reset'])->middleware('throttle:20,1')->name('password.update');

Route::get('/', fn () => redirect()->route(Auth::check() ? 'admin' : 'login'))->name('inicio');
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/totem/img/uploads/{filename}', function (string $filename) {
    abort_unless(preg_match('/^[a-zA-Z0-9_.-]+$/', $filename), 404);
    abort_unless(Storage::disk('public')->exists('media/legacy/'.$filename), 404);

    return redirect('/storage/media/legacy/'.$filename, 301);
})->name('media.legacy');

Route::middleware('throttle:20,1')->group(function (): void {
    Route::get('/parear/{code}', [DeviceController::class, 'pairingForm'])
        ->where('code', '[0-9]{6}')->name('device.pair.form');
    Route::post('/parear', [DeviceController::class, 'pair'])->name('device.pair');
});

Route::middleware('device')->group(function (): void {
    Route::post('/device/questionnaire', [QuestionnaireController::class, 'store'])
        ->middleware('throttle:30,1')->name('device.questionnaire');
    Route::get('/totem/exibir/{pagina}', [TotemPublicController::class, 'exibir'])
        ->whereNumber('pagina')->name('totem.exibir');
    Route::get('/device/manifest', [DeviceController::class, 'manifest'])->name('device.manifest');
    Route::post('/device/heartbeat', [DeviceController::class, 'heartbeat'])
        ->middleware('throttle:10,1')->name('device.heartbeat');
    Route::post('/device/telemetry', [DeviceController::class, 'telemetry'])
        ->middleware('throttle:300,1')->name('device.telemetry');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/minha-conta/senha', [AccountController::class, 'password'])->name('account.password.edit');
    Route::put('/minha-conta/senha', [AccountController::class, 'changePassword'])->middleware('throttle:6,1')->name('account.password.update');
    Route::get('/minha-conta', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/minha-conta', [AccountController::class, 'update'])->middleware('throttle:6,1')->name('account.update');
    Route::get('/notificacoes', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notificacoes/{id}/lida', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/pairing', [DeviceController::class, 'pairingIndex'])->name('pairing.index');
    Route::post('/pairing', [DeviceController::class, 'pairWithAccount'])->middleware('throttle:10,1')->name('pairing.store');
    Route::get('/admin', [AdminController::class, 'index'])->middleware('can:edit-content')->name('admin');

    Route::middleware('can:edit-content')->prefix('gestao-conteudo')->group(function (): void {
        Route::post('/editor/upload', [VisualEditorController::class, 'upload'])->name('editor-visual.upload');
        Route::put('/{pagina}/editor', [VisualEditorController::class, 'save'])->whereNumber('pagina')->name('editor-visual.save');
        Route::post('/{pagina}/enviar-revisao', [VisualEditorController::class, 'submit'])->whereNumber('pagina')->name('editor-visual.submit');
        Route::post('/{pagina}/publicar-editor', [VisualEditorController::class, 'publish'])->whereNumber('pagina')->name('editor-visual.publish');
        Route::post('/{pagina}/duplicar', [PaginaController::class, 'duplicate'])->name('gestao-conteudo.duplicate');
        Route::get('/{pagina}/visualizar', [TotemPublicController::class, 'preview'])->name('gestao-conteudo.preview');
        Route::get('/{pagina}/historico', [ReviewController::class, 'history'])->name('revisions.index');
    });

    Route::resource('gestao-conteudo', PaginaController::class)
        ->only(['index', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['gestao-conteudo' => 'gestao_conteudo'])
        ->middleware('can:edit-content');

    Route::middleware('can:administer')->group(function (): void {
        Route::get('/avaliacoes', [QuestionnaireController::class, 'index'])->name('questionnaires.index');
        Route::post('/secoes', [SecaoController::class, 'store'])->name('secoes.store');
        Route::put('/secoes/{secao}', [SecaoController::class, 'update'])->whereNumber('secao')->name('secoes.update');
        Route::put('/secoes/{secao}/ordem', [SecaoController::class, 'reorder'])->whereNumber('secao')->name('secoes.reorder');
        Route::post('/gestao-conteudo/{pagina}/definir-inicial', [PaginaController::class, 'setHome'])
            ->name('gestao-conteudo.set-home');
        Route::get('/revisoes', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/revisoes/{review}/visualizar', [ReviewController::class, 'preview'])->name('reviews.preview');
        Route::post('/revisoes/{review}/aprovar', [ReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/revisoes/{review}/devolver', [ReviewController::class, 'reject'])->name('reviews.reject');
        Route::post('/paginas/{pagina}/revisoes/{revision}/restaurar', [ReviewController::class, 'restore'])
            ->name('reviews.restore');
        Route::resource('usuarios', UserController::class)->only(['index', 'store', 'update'])->parameters(['usuarios' => 'user']);
        Route::post('/usuarios/{user}/revogar-sessoes', [UserController::class, 'revokeSessions'])
            ->middleware('throttle:6,1')
            ->name('usuarios.revoke-sessions');
        Route::post('/usuarios/{user}/resetar-senha', [UserController::class, 'resetPassword'])
            ->middleware('throttle:6,1')->name('usuarios.reset-password');
    });

    Route::middleware('can:edit-content')->group(function (): void {
        Route::resource('midias', MediaController::class)->only(['index', 'store', 'destroy'])->parameters(['midias' => 'medium']);
    });

    Route::prefix('totens')->name('admin.totens.')->middleware('can:pair-device')->group(function (): void {
        Route::get('/', [TotemController::class, 'index'])->name('index');
        Route::get('/monitoramento/tempo-real', [TotemController::class, 'status'])->name('status');
        Route::post('/{totem}/acesso', [TotemController::class, 'acesso'])->whereNumber('totem')->middleware('throttle:20,1')->name('acesso');
        Route::get('/{totem}', [TotemController::class, 'show'])->whereNumber('totem')->name('show');
    });

    Route::prefix('totens')->name('admin.totens.')->middleware('can:administer')->group(function (): void {
        Route::post('/', [TotemController::class, 'store'])->name('store');
        Route::put('/{totem}', [TotemController::class, 'update'])->whereNumber('totem')->name('update');
        Route::delete('/{totem}', [TotemController::class, 'destroy'])->whereNumber('totem')->name('destroy');
        Route::post('/{totem}/recarregar', [TotemController::class, 'reload'])->name('reload');
        Route::post('/{totem}/revogar', [TotemController::class, 'revoke'])->name('revoke');
    });

    Route::get('/relatorios', [RelatorioController::class, 'index'])
        ->middleware('can:administer')->name('relatorios');
});
