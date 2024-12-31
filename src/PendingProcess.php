<?php

namespace Bagel\ProcessSsh;

use Illuminate\Process\PendingProcess as BasePendingProcess;

/**
 * PendingProcess handles the construction and execution of SSH commands.
 */
class PendingProcess extends BasePendingProcess
{
    protected string $host;

    protected ?string $user;

    protected ?string $password;

    protected ?int $port = 22; // Default SSH port

    protected array $extraOptions = [];

    /**
     * Set configuration for the SSH connection.
     */
    public function setConfig(array $config)
    {
        if (! isset($config['host'])) {
            throw new \InvalidArgumentException('Host is required for SSH connections.');
        }

        // Assign configuration values to class properties
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }

            // Handle private key option separately
            if ($key == 'private_key') {
                $this->extraOptions['private_key'] = '-i '.$value;
            }
        }

        return $this;
    }

    /**
     * Build the SSH command string(s).
     */
    public function buildCommand(array|string|null $command): array|string|null
    {
        if (in_array($this->host, ['local', 'localhost', '127.0.0.1'])) {
            // Return the command directly for local execution
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

        return $commandsWrapped;
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
        if ($this->password !== null) {
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
    protected function exceptionCondition($command): bool
    {
        return is_array($command);
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
     * Convert the command to a Symfony Process object.
     */
    protected function toSymfonyProcess(array|string|null $command)
    {
        $command = $this->buildCommand($command);

        // Combine commands into a single string
        $command = implode(' && ', $command);

        return parent::toSymfonyProcess($command);
    }
}
