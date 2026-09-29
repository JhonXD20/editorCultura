<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\TotemController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ConteudoController;

Auth::routes();

Route::get('/admin', function () {
    return view('admin');
})->middleware('auth')->name('admin');
//Rotas de Conteudos
Route::resource('gestao-conteudo', PaginaController::class)->parameters([
    'gestao-conteudo' => 'gestao_conteudo'
])->middleware('auth');
Route::post('/gestao-conteudo/salvar', [ConteudoController::class, 'store'])->name('conteudos.store');
Route::post('/gestao-conteudo/salvar-massa', [ConteudoController::class, 'salvarEmMassa'])->name('conteudos.salvar-em-massa');
Route::post('/gestao-conteudo/upload-imagem', [ConteudoController::class, 'uploadImagem'])->name('conteudos.upload-imagem');;
// Rota para a ação de publicar conteúdo
Route::post('/gestao-conteudo/publicar', [ConteudoController::class, 'publicar'])->name('conteudos.publicar');

Route::resource('totens', TotemController::class)->middleware('auth');

Route::get('/relatorios', function () {
    return view('relatorios');
})->middleware('auth')->name('relatorios');

Route::get('/home', [HomeController::class, 'index'])->name('home');
