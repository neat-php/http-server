<?php

namespace Neat\Http\Server;

use Neat\Http\Request;
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
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
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
            return new CallableHandler(function (Request $request) use ($handler, $middleware): Response {
                return array_shift($middleware)->process($request, $this->stack($handler, $middleware));
            });
        }

        return $handler;
    }
}
