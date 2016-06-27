<?php

namespace eLife\Api;

use Exception;
use OutOfBoundsException;

class ExperimentNotFound extends OutOfBoundsException
{
    final public static function fromNumber(
        int $number,
        Exception $previous = null
    ) : ExperimentNotFound
    {
        return new ExperimentNotFound('Could not find experiment ' . $number, 0,
            $previous);
    }
}