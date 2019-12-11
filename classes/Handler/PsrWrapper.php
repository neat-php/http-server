<?php

namespace Neat\Http\Server\Handler;

use Neat\Http\Request;
use Neat\Http\Server\Handler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PsrWrapper implements RequestHandlerInterface
{
    /** @var Handler */
    private $handler;

    /**
     * PSR Wrapper constructor
     *
     * @param $handler
     */
    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handler->handle(new Request($request))->psr();
    }
}
