<?php

use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallController;

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

Route::get('/install', function () {
    return view('install');
});

Route::get('/test', [TestController::class, 'test'])->name('test');

Route::get('/experience/init/{shopDomain}', [ExperienceController::class, 'init'])
    ->name('experience.init')
    ->middleware('validate.shop.infos');
Route::get('/{platformType}/{shopDomain}/experience/{requestPage?}', [ExperienceController::class, 'resume'])
    ->name('experience.resume')
    ->middleware('validate.shop.infos');
