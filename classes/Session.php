<?php

namespace Neat\Http\Server;

class Session
{
    protected array $session;

    public function __construct()
    {
        if ($this->active()) {
            $this->session =& $_SESSION;
        }
    }

    public function active(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function start(): void
    {
        if (!$this->active()) {
            session_start();
            $this->session =& $_SESSION;
        } elseif (!isset($this->session)) {
            $this->session =& $_SESSION;
        }
    }

    public function commit(): void
    {
        if ($this->active()) {
            session_commit();
        }
    }

    public function name(): string
    {
        return session_name();
    }

    /**
     * @return false|string
     */
    public function id()
    {
        return session_id();
    }

    public function all(): array
    {
        $this->start();

        return $this->session;
    }

    /**
     * Has variable?
     *
     * @param string|int $var
     */
    public function has($var): bool
    {
        $this->start();

        return isset($this->session[$var]);
    }

    /**
     * Get variable
     *
     * @param string|int $var
     * @return mixed
     */
    public function get($var)
    {
        $this->start();

        return $this->session[$var] ?? null;
    }

    /**
     * Set variable
     *
     * @param string|int $var
     * @param mixed      $value
     */
    public function set($var, $value): void
    {
        $this->start();

        $this->session[$var] = $value;
    }

    /**
     * Unset variable
     *
     * @param string|int $var
     */
    public function unset($var): void
    {
        unset($this->session[$var]);
    }
}
