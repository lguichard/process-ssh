<?php

namespace Bagel\ProcessSsh;

use Illuminate\Process\Factory;

class ProcessSsh extends Factory
{
    protected array $config = [];

    protected bool $handleSsh = false;

    /**
     * Set the SSH configuration.
     */
    public function ssh(array $config): self
    {
        $this->config = $config;

        $this->handleSsh = true;

        return $this;
    }

    /**
     * Get the SSH configuration.
     */
    public function sshConfig(): array
    {
        return $this->config;
    }

    /**
     * Disable strict host key checking for SSH.
     */
    public function disableStrictHostKeyChecking(): self
    {
        $this->config['extraOptions'][] = '-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null';

        return $this;
    }

    /**
     * Add an extra option to the SSH configuration.
     */
    public function addExtraOption(string $option): self
    {
        $this->config['extraOptions'][] = $option;

        return $this;
    }

    public function pool(callable $callback)
    {
        if ($this->handleSsh) {
            throw new \InvalidArgumentException('Cannot pool processes with SSH enabled.');
        }

        return parent::pool($callback);
    }

    public function pipe(callable|array $callback, ?callable $output = null)
    {
        if ($this->handleSsh) {
            throw new \InvalidArgumentException('Cannot pipe processes with SSH enabled.');
        }

        return parent::pipe($callback, $output);
    }

    public function concurrently(callable $callback, ?callable $output = null)
    {

        if ($this->handleSsh) {
            throw new \InvalidArgumentException('Cannot concurrently processes with SSH enabled.');
        }

        return parent::concurrently($callback, $output);
    }

    /**
     * Create a new pending process instance.
     */
    public function newPendingProcess(): PendingProcess
    {
        return (new PendingProcess($this))
            ->setConfig($this->config, $this->handleSsh)
            ->withFakeHandlers($this->fakeHandlers);
    }
}
