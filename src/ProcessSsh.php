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

    public function useMultiplexing(string $controlPath = '', string $controlMaster = 'auto', string $controlPersist = '10m'): self
    {
        if ($controlPath === '') {
            $controlPath = '/tmp/ssh_mux_%h';
        }

        $this->config['extraOptions'][] = '-o ControlMaster='.$controlMaster.' -o ControlPath='.$controlPath.' -o ControlPersist='.$controlPersist;

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

    public function pipe(callable|array $callback, ?callable $output = null)
    {
        if ($this->handleSsh) {
            throw new \InvalidArgumentException('Cannot pipe processes with SSH enabled.');
        }

        return parent::pipe($callback, $output);
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
