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

it('Process run without user / password not set', function () {
    Process::fake();

    $process = Process::ssh([
        'host' => 'example.com',
    ])
        ->disableStrictHostKeyChecking()
        ->run('ls -al');

    expect($process->command())->toBe("ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null example.com 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'ls -al'.PHP_EOL.'EOF-PROCESS-SSH');

    Process::assertRan('ls -al');
});

it('Process run all parameters', function () {
    Process::fake();

    $process = Process::ssh([
        'host' => 'example.com',
        'user' => 'ubuntu',
        'password' => 'password',
        'port' => 22,
        'extraOptions' => [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
        ],
    ])
        ->disableStrictHostKeyChecking()
        ->run('ls -al');

    expect($process->command())->toBe("sshpass -p 'password' ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ubuntu@example.com 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'ls -al'.PHP_EOL.'EOF-PROCESS-SSH');

    Process::assertRan('ls -al');
});

it('Process run with private key', function () {
    Process::fake();

    $process = Process::ssh([
        'host' => 'example.com',
        'user' => 'ubuntu',
        'port' => 22,
        'private_key' => '/path/to/key',
    ])
        ->disableStrictHostKeyChecking()
        ->run('ls -al');

    expect($process->command())->toBe("ssh -i /path/to/key -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ubuntu@example.com 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'ls -al'.PHP_EOL.'EOF-PROCESS-SSH');

    Process::assertRan('ls -al');
});

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

it('Process can run normaly', function () {
    Process::fake();

    Process::run('ls');

    Process::assertRan('ls');

    Process::assertRan(function (PendingProcess $process, FakeProcessResult $result) {
        return $process->command === 'ls' &&
               $process->timeout === 60;
    });
});

it('Process can start normaly', function () {
    Process::fake();

    Process::start('ls');

    Process::assertRan('ls');

    Process::assertRan(function (PendingProcess $process, FakeProcessResult $result) {
        return $process->command === 'ls' &&
               $process->timeout === 60;
    });
});

it('Process ssh can run', function () {
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

it('Process ssh can start', function () {
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
