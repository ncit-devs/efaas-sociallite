<?php

namespace Ncit\Efaas\Socialite;

use Ncit\Efaas\Socialite\EfaasProvider;
use Illuminate\Support\ServiceProvider AS LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $socialite = $this->app->make(\Laravel\Socialite\Contracts\Factory::class);

        $socialite->extend(
            'efaas',
            function ($app) use ($socialite) {
                $config = $app['config']['services.efaas'];
                return $socialite->buildProvider(EfaasProvider::class, $config);
            }
        );

        $this->registerRoutes();
    }

    /**
     * Register the eFaas routes
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if (EfaasProvider::$registersOneTapRoute) {
            $this->loadRoutesFrom(dirname(__DIR__).'/routes/web.php');
        }
    }
}
