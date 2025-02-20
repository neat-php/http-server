<?php

namespace Neat\Http\Server\Test;

function trim($value)
{
    if (is_string($value)) {
        return \trim($value);
    }
    return $value;
}
