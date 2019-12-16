<?php

namespace Neat\Http\Server;

use Neat\Http\ServerRequest;
use Neat\Http\Response;

interface Handler
{
    /**
     * @param ServerRequest $request
     * @return Response
     */
    public function handle(ServerRequest $request): Response;
}
