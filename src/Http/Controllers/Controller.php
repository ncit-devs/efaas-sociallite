<?php

namespace Ncit\Efaas\Socialite\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Redirect the efaas one tap login requests to eFaas
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function login(Request $request)
    {
        return Socialite::driver('efaas')->redirect();
    }
}
