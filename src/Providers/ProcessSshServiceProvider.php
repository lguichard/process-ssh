<?php

namespace Bagel\ProcessSsh\Providers;

use Illuminate\Process\Factory;
use Bagel\ProcessSsh\ProcessSsh;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class ProcessSshServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Factory::class, function () {
            return new ProcessSsh;
        });
    }
}
