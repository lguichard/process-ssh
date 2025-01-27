<?php

use Bagel\ProcessSsh\ProcessSsh;
use Bagel\ProcessSsh\Providers\ProcessSshServiceProvider;
use Illuminate\Process\Factory;
use Orchestra\Testbench\TestCase;

uses(TestCase::class)->beforeEach(function (): void {
    $this->app->register(ProcessSshServiceProvider::class);
});

it('binds ProcessSsh to Factory', function (): void {
    $factory = app(Factory::class);

    expect($factory)->toBeInstanceOf(ProcessSsh::class);
});
