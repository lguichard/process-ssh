<?php

use Bagel\ProcessSsh\ProcessSsh;

it('test', function (): void {
    $example = new ProcessSsh;

    expect($example)->toBeObject();
});
