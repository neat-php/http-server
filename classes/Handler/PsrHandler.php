<?php

namespace Neat\Http\Server\Handler;

use Neat\Http\ServerRequest;
use Neat\Http\Response;
use Neat\Http\Server\Handler;
use Psr\Http\Server\RequestHandlerInterface;

class PsrHandler implements Handler
{
    /** @var RequestHandlerInterface */
    private $handler;

    /**
     * Handler constructor
     *
     * @param RequestHandlerInterface $handler
     */
    public function __construct(RequestHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @return RequestHandlerInterface
     */
    public function psr(): RequestHandlerInterface
    {
        return $this->handler;
    }

    /**
     * @param ServerRequest $request
     * @return Response
     */
    public function handle(ServerRequest $request): Response
    {
        return new Response($this->handler->handle($request->psr()));
    }
}
