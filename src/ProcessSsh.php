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
