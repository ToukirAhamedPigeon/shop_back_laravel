<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Redirect /swagger/index.html to /docs
Route::get('/swagger/index.html', function () {
    return redirect('/docs');
});

// Also redirect /swagger to /docs
Route::get('/swagger', function () {
    return redirect('/docs');
});
