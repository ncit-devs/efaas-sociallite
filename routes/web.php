<?php

use Illuminate\Support\Facades\Route;
use Ncit\Efaas\Socialite\Http\Controllers\Controller;

Route::middleware(['web', 'guest'])
    ->get('/efaas-one-tap-login', [Controller::class, 'oneTapLogin'])
    ->name('efaas.one-tap-login');
