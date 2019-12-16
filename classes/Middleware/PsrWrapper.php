<?php

namespace Neat\Http\Server\Middleware;

use Neat\Http\ServerRequest;
use Neat\Http\Server\Handler\PsrHandler;
use Neat\Http\Server\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PsrWrapper implements MiddlewareInterface
{
    /** @var Middleware */
    private $middleware;

    /**
     * PSR Wrapper constructor
     *
     * @param Middleware $middleware
     */
    public function __construct(Middleware $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middleware->process(new ServerRequest($request), new PsrHandler($handler))->psr();
    }
}
