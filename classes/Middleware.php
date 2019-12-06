<?php

namespace Neat\Http\Server;

use Neat\Http\Request;
use Neat\Http\Response;

interface Middleware
{
    /**
     * @param Request $request
     * @param Handler $handler
     * @return Response
     */
    public function process(Request $request, Handler $handler): Response;
}
