<?php

namespace Neat\Http\Server\Handler;

use Neat\Http\Server\Handler;
use Neat\Http\Server\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PsrWrapper implements RequestHandlerInterface
{
    private Handler $handler;

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handler->handle(new Request($request))->psr();
    }
}
