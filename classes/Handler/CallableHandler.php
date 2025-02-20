<?php

namespace Neat\Http\Server\Handler;

use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Request;

class CallableHandler implements Handler
{
    /** @var callable */
    private $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    public function callable(): callable
    {
        return $this->handler;
    }

    public function handle(Request $request): Response
    {
        return ($this->handler)($request);
    }
}
