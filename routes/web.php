<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/checkout', [App\Http\Controllers\HomeController::class, 'checkout'])->name('checkout');

Route::get('/cancel/subscription/{id}', [App\Http\Controllers\HomeController::class, 'cancelSubscription'])->name('cancel.subscription');
