<?php

namespace Bagel\ProcessSsh\Providers;

use Bagel\ProcessSsh\ProcessSsh;
use Illuminate\Process\Factory;
use Illuminate\Support\ServiceProvider;

class ProcessSshServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Factory::class, fn (): \Bagel\ProcessSsh\ProcessSsh => new ProcessSsh);
    }
}
