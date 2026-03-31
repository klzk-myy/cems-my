<?php

use Illuminate\Support\Facades\Route;

// Auth routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');
