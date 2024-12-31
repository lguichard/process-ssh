<?php

namespace Bagel\ProcessSsh;

use Illuminate\Process\PendingProcess as BasePendingProcess;

class PendingProcess extends BasePendingProcess
{
    protected function toSymfonyProcess(array|string|null $command)
    {
        return parent::toSymfonyProcess($command);
    }
}
