<?php

use Illuminate\Support\Facades\Route;

Auth::routes();

Route::get('/admin', function () {
    return view('admin');
})->middleware('auth')->name('admin');
Route::get('/gestao-conteudo', function () {
    return view('gestao-conteudo');
})->middleware('auth')->name('gestao-conteudo');
Route::get('/totens', function () {
    return view('totens');
})->middleware('auth')->name('totens');
Route::get('/relatorios', function () {
    return view('relatorios');
})->middleware('auth')->name('relatorios');



Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
