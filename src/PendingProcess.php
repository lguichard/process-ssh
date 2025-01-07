<?php

namespace Bagel\ProcessSsh;

use Illuminate\Process\PendingProcess as BasePendingProcess;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * PendingProcess handles the construction and execution of SSH commands.
 */
class PendingProcess extends BasePendingProcess
{
    protected string $host;

    protected ?string $user = null;

    protected ?string $password = null;

    protected ?int $port = 22; // Default SSH port

    protected array $extraOptions = [];

    private bool $handleSsh = false;

    /**
     * Override the command string to handle SSH commands.
     */
    public function command(array|string $command)
    {
        $command = $this->buildCommand($command);

        $this->command = $command;

        return $this;
    }

    /**
     * Set configuration for the SSH connection.
     */
    public function setConfig(array $config, bool $handleSsh): static
    {
        $this->handleSsh = $handleSsh;

        if (! $this->handleSsh && $config === []) {
            return $this;
        }

        if (! isset($config['host'])) {
            throw new \InvalidArgumentException('Host is required for SSH connections.');
        }

        // Assign configuration values to class properties
        foreach ($config as $key => $value) {
            // Handle private key option separately
            if ($key == 'private_key') {
                $this->extraOptions[] = '-i '.$value;
            }

            if (property_exists($this, $key)) {
                if ($key == 'extraOptions') {
                    $this->extraOptions = array_merge($this->extraOptions, $value);
                } else {
                    $this->{$key} = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Build the SSH command string(s).
     */
    public function buildCommand(array|string|null $command): array|string|null
    {
        if ($command === '' || $command === '0' || $command === [] || $command === null) {
            return $command;
        }

        if (! $this->handleSsh) {
            return $command;
        }

        $commands = (array) $command; // Ensure the commands are in array format
        $commandsWrapped = [];

        foreach ($commands as $commandString) {
            $passwordCommand = $this->buildPasswordCommand();
            $extraOptions = implode(' ', $this->buildExtraOptions());
            $delimiter = 'EOF-PROCESS-SSH';
            $bash = "'bash -se'";

            // Wrap the command for SSH execution
            $commandsWrapped[] = "{$passwordCommand}ssh {$extraOptions} {$this->buildTarget()} {$bash} << \\$delimiter".PHP_EOL
                .$commandString.PHP_EOL
                .$delimiter;
        }

        return implode(' && ', $commandsWrapped);
    }

    /**
     * Build additional SSH options.
     */
    protected function buildExtraOptions(): array
    {
        return array_values($this->extraOptions);
    }

    /**
     * Build the SSH password command.
     */
    protected function buildPasswordCommand(): string
    {
        if ($this->password !== null && $this->password !== '') {
            return 'sshpass -p \''.$this->password.'\' ';
        }

        return '';
    }

    /**
     * Build the SSH target string (user@host).
     */
    protected function buildTarget(): string
    {
        if ($this->user === null) {
            return $this->host;
        }

        return "{$this->user}@{$this->host}";
    }

    /**
     * Check if the command is invalid for SSH execution.
     *
     * @param  mixed  $command  The command to check.
     * @return bool True if the command is invalid, otherwise false.
     */
    protected function exceptionCondition(mixed $command): bool
    {
        return is_array($command) && $this->handleSsh;
    }

    /**
     * Run the provided SSH command(s).
     */
    public function run(array|string|null $command = null, ?callable $output = null)
    {
        if ($this->exceptionCondition($command)) {
            throw new \InvalidArgumentException('Array commands are not supported for SSH connections.');
        }

        return parent::run($command, $output);
    }

    /**
     * Start the execution of SSH command(s).
     */
    public function start(array|string|null $command = null, ?callable $output = null)
    {
        if ($this->exceptionCondition($command)) {
            throw new \InvalidArgumentException('Array commands are not supported for SSH connections.');
        }

        return parent::start($command, $output);
    }

    /**
     * Get the fake handler for the given command, if applicable.
     */
    protected function fakeFor(string $command)
    {
        return (new Collection($this->fakeHandlers))
            ->first(fn ($handler, $pattern): bool => $pattern === '*' || Str::contains($command, $pattern));
    }

    /**
     * Convert the command to a Symfony Process object.
     */
    protected function toSymfonyProcess(array|string|null $command)
    {
        $command = $this->buildCommand($command);

        return parent::toSymfonyProcess($command);
    }
}
