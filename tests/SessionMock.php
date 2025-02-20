<?php

namespace Neat\Http\Server\Test;

use Neat\Http\Server\Session;

class SessionMock extends Session
{
    private bool $active = false;

    /**
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(?array &$session = null)
    {
        $this->session = [];
        if ($session !== null) {
            $this->session = &$session;
        }
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function start(): void
    {
        $this->active = true;
    }
}
