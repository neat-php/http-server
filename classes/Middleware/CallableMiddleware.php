<?php

namespace Neat\Http\Server\Middleware;

use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Middleware;
use Neat\Http\Server\Request;

class CallableMiddleware implements Middleware
{
    /** @var callable */
    private $middleware;

    public function __construct(callable $middleware)
    {
        $this->middleware = $middleware;
    }

    public function callable(): callable
    {
        return $this->middleware;
    }

    public function process(Request $request, Handler $handler): Response
    {
        return ($this->middleware)($request, $handler);
    }
}
