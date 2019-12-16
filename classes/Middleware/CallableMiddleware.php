<?php

namespace Neat\Http\Server\Middleware;

use Neat\Http\Response;
use Neat\Http\ServerRequest;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Middleware;

class CallableMiddleware implements Middleware
{
    /** @var callable */
    private $middleware;

    /**
     * Middleware constructor
     *
     * @param callable $middleware
     */
    public function __construct(callable $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * @return callable
     */
    public function callable(): callable
    {
        return $this->middleware;
    }

    /**
     * @param ServerRequest $request
     * @param Handler $handler
     * @return Response
     */
    public function process(ServerRequest $request, Handler $handler): Response
    {
        return ($this->middleware)($request, $handler);
    }
}
