<?php

namespace Neat\Http\Server;

use Neat\Http\Response;

interface Middleware
{
    public function process(Request $request, Handler $handler): Response;
}
