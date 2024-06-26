<?php

namespace ModooId\LaravelTools;

use Illuminate\Support\ServiceProvider;

class LaravelToolsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            \ModooId\LaravelTools\Console\Commands\MakeAction::class,
            \ModooId\LaravelTools\Console\Commands\InstallCodeFormatter::class,
            \ModooId\LaravelTools\Console\Commands\InstallSingleSignOn::class,
        ]);
    }
}