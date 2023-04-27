<?php

use App\Http\Controllers\EventsController;
use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/install', [InstallController::class, 'init'])
->name('shop.init');

Route::get('/authorize', [InstallController::class, 'install'])
->name('shop.install');

Route::post('/events', [EventsController::class, 'register'])
    ->name('events.register')
    ->middleware('validate.token');
