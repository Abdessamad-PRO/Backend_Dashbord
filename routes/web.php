<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route pour la page de détails d'une demande d'inscription
Route::get('/admin/registration/{id}', function ($id) {
    return view('admin.registration-details', ['registration_id' => $id]);
})->middleware(['auth'])->name('admin.registration.details');

// Route pour la page de réinitialisation de mot de passe initial
Route::get('/set-initial-password', function () {
    return view('auth.set-initial-password');
})->name('set.initial.password');
