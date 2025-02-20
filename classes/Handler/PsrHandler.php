<?php

namespace Neat\Http\Server\Handler;

use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Neat\Http\Server\Request;
use Psr\Http\Server\RequestHandlerInterface;

class PsrHandler implements Handler
{
    private RequestHandlerInterface $handler;

    public function __construct(RequestHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public function psr(): RequestHandlerInterface
    {
        return $this->handler;
    }

    public function handle(Request $request): Response
    {
        return new Response($this->handler->handle($request->psr()));
    }
}
