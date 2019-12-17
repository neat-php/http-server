<?php

namespace Neat\Http\Server;

class Session
{
    /** @var array */
    protected $session;

    /**
     * Session constructor
     */
    public function __construct()
    {
        if ($this->active()) {
            $this->session =& $_SESSION;
        }
    }

    /**
     * Session active?
     *
     * @return bool
     */
    public function active(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Start (open) the session
     */
    public function start()
    {
        if (!$this->active()) {
            session_start();
            $this->session =& $_SESSION;
        } elseif (!isset($this->session)) {
            $this->session =& $_SESSION;
        }
    }

    /**
     * Commit (write and close) the session
     */
    public function commit()
    {
        if ($this->active()) {
            session_commit();
        }
    }

    /**
     * Get session name (cookie name)
     *
     * @return string
     */
    public function name()
    {
        return session_name();
    }

    /**
     * Get session id (cookie value)
     *
     * @return string
     */
    public function id()
    {
        return session_id();
    }

    /**
     * Get all variables
     *
     * @return array
     */
    public function all()
    {
        $this->start();

        return $this->session;
    }

    /**
     * Has variable?
     *
     * @param string|int $var
     * @return bool
     */
    public function has($var)
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
    public function set($var, $value)
    {
        $this->start();

        $this->session[$var] = $value;
    }

    /**
     * Unset variable
     *
     * @param string|int $var
     */
    public function unset($var)
    {
        unset($this->session[$var]);
    }
}
