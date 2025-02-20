<?php

namespace Neat\Http\Server;

use Neat\Http\Response;

interface Handler
{
    public function handle(Request $request): Response;
}
