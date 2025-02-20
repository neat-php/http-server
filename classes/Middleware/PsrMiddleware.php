<?php

namespace Neat\Http\Server\Middleware;

use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Middleware;
use Neat\Http\Server\Request;
use Psr\Http\Server\MiddlewareInterface;

class PsrMiddleware implements Middleware
{
    private MiddlewareInterface $middleware;

    public function __construct(MiddlewareInterface $middleware)
    {
        $this->middleware = $middleware;
    }

    public function psr(): MiddlewareInterface
    {
        return $this->middleware;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $psrHandler
            = $handler instanceof Handler\PsrHandler
            ? $handler->psr()
            : new Handler\PsrWrapper($handler);

        return new Response($this->middleware->process($request->psr(), $psrHandler));
    }
}
