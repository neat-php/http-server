<?php

namespace Neat\Http\Server\Handler;

use Neat\Http\Request;
use Neat\Http\Response;
use Neat\Http\Server\Handler;

class CallableHandler implements Handler
{
    /** @var callable */
    private $handler;

    /**
     * Handler constructor
     *
     * @param callable $handler
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @return callable
     */
    public function callable(): callable
    {
        return $this->handler;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        return ($this->handler)($request);
    }
}
