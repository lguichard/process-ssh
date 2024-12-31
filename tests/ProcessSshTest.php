<?php

use Bagel\ProcessSsh\PendingProcess;
use Bagel\ProcessSsh\Providers\ProcessSshServiceProvider;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\Process;
use Orchestra\Testbench\TestCase;

uses(TestCase::class)->beforeEach(function () {
    $this->app->register(ProcessSshServiceProvider::class);
});

it('process config set', function () {
    $process = Process::ssh([
        'host' => 'example.com',
        'user' => 'ubuntu',
        'port' => 22,
        'extraOptions' => [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
        ],
        'private_key' => '/path/to/key',
    ]);

    expect($process->sshConfig()['host'])->toBe('example.com');
    expect($process->sshConfig()['user'])->toBe('ubuntu');
    expect($process->sshConfig()['port'])->toBe(22);
    expect($process->sshConfig()['extraOptions'])->toBe([
        '-o StrictHostKeyChecking=no',
        '-o UserKnownHostsFile=/dev/null',
    ]);
    expect($process->sshConfig()['private_key'])->toBe('/path/to/key');
});

it('process add extra options', function () {
    $process = Process::ssh([
        'host' => 'example.com',
        'user' => 'ubuntu',
        'port' => 22,
    ])->addExtraOption('-o StrictHostKeyChecking=no');

    expect($process->sshConfig()['extraOptions'])->toBe([
        '-o StrictHostKeyChecking=no',
    ]);
});

it('process disableStrictHostKeyChecking', function () {
    $process = Process::ssh([
        'host' => 'example.com',
        'user' => 'ubuntu',
        'port' => 22,
    ])->disableStrictHostKeyChecking();

    expect($process->sshConfig()['extraOptions'])->toBe([
        '-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null',
    ]);
});

it('exception thrown when host is not set', function () {
    Process::ssh([])->run('ls');
})->throws(InvalidArgumentException::class, 'Host is required for SSH connections.');

it('exception thrown process run with array', function () {
    Process::fake();

    Process::ssh([
        'host' => '127.0.0.1',
    ])->run(['ls', '-al']);

})->throws(InvalidArgumentException::class, 'Array commands are not supported for SSH connections');

it('exception thrown process start with array', function () {
    Process::fake();

    Process::ssh([
        'host' => '127.0.0.1',
    ])->start(['ls', '-al']);

})->throws(InvalidArgumentException::class, 'Array commands are not supported for SSH connections');

it('Process can run', function () {
    Process::fake();

    Process::ssh([
        'host' => 'example.com',
        'user' => 'ubuntu',
        'password' => 'password',
        'port' => 22,
    ])
        ->run('ls');

    Process::assertRan('ls');

    Process::assertRan(function (PendingProcess $process, FakeProcessResult $result) {
        return $process->command === 'ls' &&
               $process->timeout === 60;
    });
});

it('Process can start', function () {
    Process::fake();

    Process::ssh([
        'host' => 'example.com',
        'user' => 'ubuntu',
        'password' => 'password',
        'port' => 22,
    ])
        ->start('ls');

    Process::assertRan('ls');

    Process::assertRan(function (PendingProcess $process, FakeProcessResult $result) {
        return $process->command === 'ls' &&
               $process->timeout === 60;
    });
});
