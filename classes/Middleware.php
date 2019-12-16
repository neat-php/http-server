<?php

namespace Neat\Http\Server;

use Neat\Http\ServerRequest;
use Neat\Http\Response;

interface Middleware
{
    /**
     * @param ServerRequest $request
     * @param Handler $handler
     * @return Response
     */
    public function process(ServerRequest $request, Handler $handler): Response;
}
