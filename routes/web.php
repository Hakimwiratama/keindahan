<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\MemoryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/albums');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::redirect('/memories', '/albums')->name('memories.index');
    Route::get('/albums', [AlbumController::class, 'index'])->name('albums.index');
    Route::get('/albums/create', [AlbumController::class, 'create'])->name('albums.create');
    Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
    Route::get('/albums/{album}', [AlbumController::class, 'show'])->whereNumber('album')->name('albums.show');
    Route::delete('/albums/{album}', [AlbumController::class, 'destroy'])->whereNumber('album')->name('albums.destroy');
    Route::get('/albums/{album}/memories/create', [MemoryController::class, 'create'])->whereNumber('album')->name('memories.create');
    Route::post('/albums/{album}/memories', [MemoryController::class, 'store'])->whereNumber('album')->name('memories.store');
    Route::get('/albums/{album}/memories/{memory}/preview', [MemoryController::class, 'preview'])->whereNumber(['album', 'memory'])->name('memories.preview');
    Route::get('/albums/{album}/memories/{memory}/download', [MemoryController::class, 'download'])->whereNumber(['album', 'memory'])->name('memories.download');
    Route::get('/albums/{album}/memories/{memory}', [MemoryController::class, 'show'])->whereNumber(['album', 'memory'])->name('memories.show');
    Route::delete('/albums/{album}/memories/{memory}', [MemoryController::class, 'destroy'])->whereNumber(['album', 'memory'])->name('memories.destroy');
});
