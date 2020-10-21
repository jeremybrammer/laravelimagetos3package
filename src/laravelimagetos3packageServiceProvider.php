<?php

namespace jeremybrammer\laravelimagetos3package;

use Illuminate\Support\ServiceProvider;

class laravelimagetos3packageServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'jeremybrammer');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'jeremybrammer');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravelimagetos3package.php', 'laravelimagetos3package');

        // Register the service the package provides.
        // $this->app->singleton('laravelimagetos3package', function ($app) {
        //     return new laravelimagetos3package;
        // });

        $this->app->bind(ImageTos3Interface::class, function($app){
            return new laravelimagetos3package;
        });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravelimagetos3package'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/laravelimagetos3package.php' => config_path('laravelimagetos3package.php'),
        ], 'laravelimagetos3package.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/jeremybrammer'),
        ], 'laravelimagetos3package.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/jeremybrammer'),
        ], 'laravelimagetos3package.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/jeremybrammer'),
        ], 'laravelimagetos3package.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
