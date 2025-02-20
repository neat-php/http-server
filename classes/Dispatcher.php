<?php

namespace Neat\Http\Server;

use Neat\Http\Response;
use Neat\Http\Server\Handler\CallableHandler;

class Dispatcher implements Handler
{
    private Handler $stack;

    public function __construct(Handler $handler, Middleware ...$middleware)
    {
        $this->stack = $this->stack($handler, $middleware);
    }

    public function handle(Request $request): Response
    {
        return $this->stack->handle($request);
    }

    /**
     * @param Middleware[] $middleware
     */
    private function stack(Handler $handler, array $middleware): Handler
    {
        if ($middleware) {
            $layer = array_shift($middleware);
            $stack = $this->stack($handler, $middleware);

            return new CallableHandler(function (Request $request) use ($layer, $stack): Response {
                return $layer->process($request, $stack);
            });
        }

        return $handler;
    }
}
