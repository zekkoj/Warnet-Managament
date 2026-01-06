<?php

use Illuminate\Support\Facades\Route;

// Login Route
Route::get('/login', function () {
    return view('login');
})->name('login');

// Dashboard Routes
Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

// PC Monitoring
Route::get('/pcs', function () {
    return view('pcs.index');
})->name('pcs.index');

// Sessions
Route::get('/sessions', function () {
    return view('sessions.index');
})->name('sessions.index');

// Orders
Route::get('/orders', function () {
    return view('orders.index');
})->name('orders.index');

// Menu Management
Route::get('/menu', function () {
    return view('menu.index');
})->name('menu.index');

// Analytics
Route::get('/analytics', function () {
    return view('analytics');
})->name('analytics');

// Rental & Order (Integrated)
Route::get('/rental-order', function () {
    return view('rental-order');
})->name('rental-order');

