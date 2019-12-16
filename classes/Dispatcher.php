<?php

namespace Neat\Http\Server;

use Neat\Http\ServerRequest;
use Neat\Http\Response;
use Neat\Http\Server\Handler\CallableHandler;

class Dispatcher implements Handler
{
    /** @var Handler[] */
    private $stack;

    /**
     * Dispatcher constructor
     *
     * @param Handler    $handler
     * @param Middleware ...$middleware
     */
    public function __construct(Handler $handler, Middleware ...$middleware)
    {
        $this->stack = $this->stack($handler, $middleware);
    }

    /**
     * @param ServerRequest $request
     * @return Response
     */
    public function handle(ServerRequest $request): Response
    {
        return $this->stack->handle($request);
    }

    /**
     * @param Handler      $handler
     * @param Middleware[] $middleware
     * @return Handler
     */
    private function stack(Handler $handler, array $middleware): Handler
    {
        if ($middleware) {
            $layer = array_shift($middleware);
            $stack = $this->stack($handler, $middleware);

            return new CallableHandler(function (ServerRequest $request) use ($layer, $stack): Response {
                return $layer->process($request, $stack);
            });
        }

        return $handler;
    }
}
