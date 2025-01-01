<p align="center">
    <img src="https://raw.githubusercontent.com/lguichard/process-ssh/master/assets/visual.webp" height="300" alt="Laravel Process over SSH">
    <p align="center">
        <a href="https://github.com/lguichard/process-ssh/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/lguichard/process-ssh/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://packagist.org/packages/lguichard/process-ssh"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/lguichard/process-ssh"></a>
        <a href="https://packagist.org/packages/lguichard/process-ssh"><img alt="Latest Version" src="https://img.shields.io/packagist/v/lguichard/process-ssh"></a>
    </p>
</p>

------
# Laravel Process over SSH

`Laravel Process over SSH` is a Laravel package that extends the `Illuminate\Process` functionality to allow command execution via SSH.

## Features

- Execute shell commands on remote servers using SSH.
- Full compatibility with Laravel's `Process` features.
- Easily configurable options like custom ports, passwords, private keys, and more.

## Installation

Install the package via Composer:

```bash
composer require lguichard/process-ssh
```

## Usage

To execute a command over SSH, use the `Process` facade:

### Basic Usage

```php
$result = Process::ssh([
        'host' => '192.168.1.10',
        'user' => 'username',
        'password' => 'your_password',
    ])
    ->run('ls -al');

if ($result->successful()) {
    echo $result->output();
} else {
    echo $result->errorOutput();
}
```

### Using Private Key Authentication

```php
$result = Process::ssh([
        'host' => '192.168.1.10',
        'user' => 'username',
        'private_key' => '/path/to/private_key',
    ])
    ->run('ls -al');
```

### Disabling Strict Host Key Checking

```php
$result = Process::ssh([
        'host' => '192.168.1.10',
        'user' => 'username',
        'private_key' => '/path/to/private_key',
    ])
    ->disableStrictHostKeyChecking()
    ->run('ls -al');
```

### Adding Extra SSH Options
```php
$result = Process::ssh([
        'host' => '192.168.1.10',
        'user' => 'username',
        'private_key' => '/path/to/private_key',
    ])
    ->addExtraOption('-o LogLevel=ERROR')
    ->addExtraOption('-o ConnectTimeout=10')
    ->run('ls -al');
```

### Use the favorites method provided by Laravel's Process class.

For more information, refer to the official documentation : https://laravel.com/docs/11.x/processes

```php
[$result1, $result2] = Process::ssh([
        'host' => '192.168.1.10',
        'user' => 'username',
        'password' => 'your_password',
    ])
    ->concurrently(function (Pool $pool) {
        $pool->command('ls -al');
        $pool->command('whoami');
    });
```

```php
$result = Process::ssh([
        'host' => '192.168.1.10',
        'user' => 'username',
        'password' => 'your_password',
    ])
    ->pool(function (Pool $pool) {
        $pool->command('ls -al');
        $pool->command('whoami');
    });
```

## Testing

To run the package's tests:

```bash
composer test
```

## Contributing

Contributions are welcome! Please submit a pull request or open an issue on GitHub.

## License

This package is open-source software licensed under the [MIT license](LICENSE.md).

For more details, visit the [GitHub repository](https://github.com/lguichard/process-ssh).

## Acknowledgments
Special thanks to Spatie's SSH package for inspiring the creation of this package.

**Skeleton PHP** was created by **[Nuno Maduro](https://twitter.com/enunomaduro)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
