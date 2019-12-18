<?php

namespace Neat\Http\Server;

use Neat\Http\Response;

interface Handler
{
    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response;
}
