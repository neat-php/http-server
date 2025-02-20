<?php

namespace Neat\Http\Server\Middleware;

use Neat\Http\Server\Handler\PsrHandler;
use Neat\Http\Server\Middleware;
use Neat\Http\Server\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PsrWrapper implements MiddlewareInterface
{
    private Middleware $middleware;

    public function __construct(Middleware $middleware)
    {
        $this->middleware = $middleware;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middleware->process(new Request($request), new PsrHandler($handler))->psr();
    }
}
