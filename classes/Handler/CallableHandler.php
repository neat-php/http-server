<?php

namespace Neat\Http\Server\Handler;

use Neat\Http\ServerRequest;
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
     * @param ServerRequest $request
     * @return Response
     */
    public function handle(ServerRequest $request): Response
    {
        return ($this->handler)($request);
    }
}
