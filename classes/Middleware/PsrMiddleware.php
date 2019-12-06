<?php

namespace Neat\Http\Server\Middleware;

use Neat\Http\Request;
use Neat\Http\Response;
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
     * @param Request $request
     * @param Handler $handler
     * @return Response
     */
    public function process(Request $request, Handler $handler): Response
    {
        $response = $this->middleware->process($request->psr(), $handler->psr());

        return new Response($response);
    }
}
