<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;


function include_bootstrap_page(string $file)
{
    $path = __DIR__ . '/../bootstrap/' . $file;
    if (!file_exists($path)) {
        return response("Page not found: $file", 404);
    }
    ob_start();
    include $path;
    $content = ob_get_clean();
    return new Response($content);
}


Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::prefix('dashboard')->group(function () {
    Route::get('/', function () {
        return include_bootstrap_page('dashboard.php');
    })->name('dashboard');
});


Route::prefix('purchases')->name('purchases.')->group(function () {
    Route::get('/', function () {
        return include_bootstrap_page('purchases.php');
    })->name('index');

    Route::get('/edit', function () {
        return include_bootstrap_page('purchase.php');
    })->name('edit');
});


Route::prefix('queue')->group(function () {
    Route::get('/', function () {
        return include_bootstrap_page('queue.php');
    })->name('queue');
});

// Rotas relacionadas a usuários
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', function () {
        return include_bootstrap_page('users.php');
    })->name('index');

    Route::get('/create', function () {
        return include_bootstrap_page('user.php');
    })->name('create');
});

// Rota genérica para arquivos PHP
Route::get('/{page}.php', function ($page) {
    $file = $page . '.php';
    return include_bootstrap_page($file);
})->where('page', '[-A-Za-z0-9_]+');

