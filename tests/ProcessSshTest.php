<?php

use Bagel\ProcessSsh\PendingProcess;
use Bagel\ProcessSsh\Providers\ProcessSshServiceProvider;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Orchestra\Testbench\TestCase;

uses(TestCase::class)->beforeEach(function (): void {
    $this->app->register(ProcessSshServiceProvider::class);

    $this->basicSshConfig = [
        'host' => '192.178.0.1',
        'user' => 'ubuntu',
        'port' => 22,
    ];
});

it('process config set', function (): void {
    $process = Process::ssh([
        'host' => '192.178.0.1',
        'user' => 'ubuntu',
        'port' => 22,
        'extraOptions' => [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
        ],
        'private_key' => '/path/to/key',
    ]);

    expect($process->sshConfig()['host'])->toBe('192.178.0.1');
    expect($process->sshConfig()['user'])->toBe('ubuntu');
    expect($process->sshConfig()['port'])->toBe(22);
    expect($process->sshConfig()['extraOptions'])->toBe([
        '-o StrictHostKeyChecking=no',
        '-o UserKnownHostsFile=/dev/null',
    ]);
    expect($process->sshConfig()['private_key'])->toBe('/path/to/key');
});

it('process add extra options', function (): void {
    $process = Process::ssh([
        'host' => '192.178.0.1',
        'user' => 'ubuntu',
        'port' => 22,
    ])->addExtraOption('-o StrictHostKeyChecking=no');

    expect($process->sshConfig()['extraOptions'])->toBe([
        '-o StrictHostKeyChecking=no',
    ]);
});

it('process disableStrictHostKeyChecking', function (): void {
    $process = Process::ssh([
        'host' => '192.178.0.1',
        'user' => 'ubuntu',
        'port' => 22,
    ])->disableStrictHostKeyChecking();

    expect($process->sshConfig()['extraOptions'])->toBe([
        '-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null',
    ]);
});

it('process useMultiplexing', function (): void {
    $process = Process::ssh([
        'host' => '192.168.85.5',
        'user' => 'ubuntu',
        'port' => 22,
    ])
        ->useMultiplexing();

    expect($process->sshConfig()['extraOptions'])->toBe([
        '-o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h -o ControlPersist=10m',
    ]);

    $process = Process::ssh([
        'host' => '192.168.85.5',
        'user' => 'ubuntu',
        'port' => 22,
    ])->useMultiplexing('/tmp/ssh_mux_%h-%p', 'yes', '5m');

    expect($process->sshConfig()['extraOptions'])->toBe([
        '-o ControlMaster=yes -o ControlPath=/tmp/ssh_mux_%h-%p -o ControlPersist=5m',
    ]);
});

it('exception thrown when host is not set', function (): void {
    Process::ssh([])->run('ls');
})->throws(InvalidArgumentException::class, 'Host is required for SSH connections.');

it('Process run without user / password not set', function (): void {
    Process::fake();

    $process = Process::ssh([
        'host' => '192.178.0.1',
    ])
        ->disableStrictHostKeyChecking()
        ->run('ls -al');

    expect($process->command())->toBe("ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null 192.178.0.1 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'ls -al'.PHP_EOL.'EOF-PROCESS-SSH');

    Process::assertRan('ls -al');
});

it('Process run all parameters', function (): void {
    Process::fake();

    $process = Process::ssh([
        'host' => '192.178.0.1',
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

    expect($process->command())->toBe("sshpass -p 'password' ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ubuntu@192.178.0.1 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'ls -al'.PHP_EOL.'EOF-PROCESS-SSH');

    Process::assertRan('ls -al');
});

it('Process run with private key', function (): void {
    Process::fake();

    $process = Process::ssh([
        'host' => '192.178.0.1',
        'user' => 'ubuntu',
        'port' => 22,
        'private_key' => '/path/to/key',
    ])
        ->disableStrictHostKeyChecking()
        ->run('ls -al');

    expect($process->command())->toBe("ssh -i /path/to/key -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ubuntu@192.178.0.1 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'ls -al'.PHP_EOL.'EOF-PROCESS-SSH');

    Process::assertRan('ls -al');
});

it('Process can run normaly', function (): void {
    Process::fake();

    Process::run('ls');

    Process::assertRan('ls');

    Process::assertRan(fn (PendingProcess $process, FakeProcessResult $result): bool => $process->command === 'ls' &&
           $process->timeout === 60);
});

it('Process can start normaly', function (): void {
    Process::fake();

    Process::start('ls');

    Process::assertRan('ls');

    Process::assertRan(fn (PendingProcess $process, FakeProcessResult $result): bool => $process->command === 'ls' &&
           $process->timeout === 60);
});

it('Process ssh can run', function (): void {
    Process::fake();

    Process::ssh([
        'host' => '192.178.0.1',
        'user' => 'ubuntu',
        'password' => 'password',
        'port' => 22,
    ])
        ->run('ls');

    Process::assertRan('ls');

    Process::assertRan(fn (PendingProcess $process, FakeProcessResult $result): bool => $process->command === 'ls' &&
           $process->timeout === 60);
});

it('Process ssh can start', function (): void {
    Process::fake();

    Process::ssh([
        'host' => '192.178.0.1',
        'user' => 'ubuntu',
        'password' => 'password',
        'port' => 22,
    ])
        ->start('ls');

    Process::assertRan('ls');

    Process::assertRan(fn (PendingProcess $process, FakeProcessResult $result): bool => $process->command === 'ls' &&
           $process->timeout === 60);
});

it('invoke process::concurrently', function (): void {
    Process::fake();

    $process = Process::concurrently(function (Pool $pool): void {
        $pool->command('ls -al');
        $pool->command('whoami');
    });

    expect($process[0]->command())->toBe('ls -al');
    expect($process[1]->command())->toBe('whoami');

    Process::assertRan('ls -al');
    Process::assertRan('whoami');
});

it('invoke process::concurrently ssh', function (): void {
    Process::fake();

    $process = Process::ssh($this->basicSshConfig)->concurrently(function (Pool $pool): void {
        $pool->command('ls -al');
        $pool->command('whoami');
    });

    expect($process[0]->command())->toBe("ssh  ubuntu@192.178.0.1 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'ls -al'.PHP_EOL.'EOF-PROCESS-SSH');
    expect($process[1]->command())->toBe("ssh  ubuntu@192.178.0.1 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'whoami'.PHP_EOL.'EOF-PROCESS-SSH');

});

it('invoke process::pool', function (): void {
    Process::fake();

    $process = Process::pool(function (Pool $pool): void {
        $pool->command('ls -al');
        $pool->command('whoami');
    });

    $results = $process->wait();
    expect($results[0]->command())->toBe('ls -al');
    expect($results[1]->command())->toBe('whoami');

    Process::assertRan('ls -al');
    Process::assertRan('whoami');
});

it('invoke process::pool ssh', function (): void {
    Process::fake();

    $process = Process::ssh($this->basicSshConfig)->pool(function (Pool $pool): void {
        $pool->command('ls -al');
        $pool->command('whoami');
    });

    $results = $process->wait();
    expect($results[0]->command())->toBe("ssh  ubuntu@192.178.0.1 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'ls -al'.PHP_EOL.'EOF-PROCESS-SSH');
    expect($results[1]->command())->toBe("ssh  ubuntu@192.178.0.1 'bash -se' << \EOF-PROCESS-SSH".PHP_EOL.'whoami'.PHP_EOL.'EOF-PROCESS-SSH');

});

it('exception thrown process run with array', function (): void {
    Process::fake();

    Process::ssh([
        'host' => '127.0.0.1',
    ])->run(['ls', '-al']);
})->throws(InvalidArgumentException::class, 'Array commands are not supported for SSH connections.');

it('can\'t pipe processes with SSH enabled', function (): void {
    Process::fake();

    Process::ssh([
        'host' => 'example.com',
        'user' => 'ubuntu',
        'password' => 'password',
        'port' => 22,
    ])->pipe(function (): void {
        //
    });
})->throws(InvalidArgumentException::class, 'Cannot pipe processes with SSH enabled.');

it('exception thrown process start with array', function (): void {
    Process::fake();

    Process::ssh([
        'host' => '127.0.0.1',
    ])->start(['ls', '-al']);

})->throws(InvalidArgumentException::class, 'Array commands are not supported for SSH connections.');

it('invoke process::pipe', function (): void {
    Process::fake();

    $process = Process::ssh($this->basicSshConfig)->pipe([
        'ls -al',
        'whoami',
    ]);
})->throws(InvalidArgumentException::class, 'Cannot pipe processes with SSH enabled.');

it('fake result Process::run', function (): void {
    Process::fake([
        'ls -al' => Process::result(
            output: 'test',
            errorOutput: '',
            exitCode: 1,
        ),
    ]);

    $process = Process::ssh($this->basicSshConfig)->run('ls -al');

    expect($process->output())->toBe("test\n");
});

it('fake result Process::start', function (): void {
    Process::fake([
        'ls -al' => Process::result(
            output: 'test',
            errorOutput: '',
            exitCode: 1,
        ),
    ]);

    $process = Process::ssh($this->basicSshConfig)->start('ls -al');

    expect($process->wait()->output())->toBe("test\n");
});
