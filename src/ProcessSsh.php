<?php

namespace Bagel\ProcessSsh;

use Illuminate\Process\Factory;

class ProcessSsh extends Factory
{
    public function newPendingProcess()
    {
        return (new PendingProcess($this))->withFakeHandlers($this->fakeHandlers);
    }
}
