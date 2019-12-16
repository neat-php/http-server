<?php

namespace Neat\Http\Server\Middleware;

use Neat\Http\Response;
use Neat\Http\ServerRequest;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Middleware;
use Psr\Http\Server\MiddlewareInterface;

class PsrMiddleware implements Middleware
{
    /** @var MiddlewareInterface */
    private $middleware;

    /**
     * Middleware constructor
     *
     * @param MiddlewareInterface $middleware
     */
    public function __construct(MiddlewareInterface $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * @return MiddlewareInterface
     */
    public function psr(): MiddlewareInterface
    {
        return $this->middleware;
    }

    /**
     * @param ServerRequest $request
     * @param Handler       $handler
     * @return Response
     */
    public function process(ServerRequest $request, Handler $handler): Response
    {
        $psrHandler
            = $handler instanceof Handler\PsrHandler
            ? $handler->psr()
            : new Handler\PsrWrapper($handler);

        return new Response($this->middleware->process($request->psr(), $psrHandler));
    }
}
