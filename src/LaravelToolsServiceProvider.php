<?php

namespace ModooId\LaravelTools;

use Illuminate\Support\ServiceProvider;

class LaravelToolsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            \ModooId\LaravelTools\Console\Commands\MakeAction::class
        ]);
    }
}