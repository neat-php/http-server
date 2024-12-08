<?php

namespace Neat\Http\Server;

use Neat\Http\Exception\MethodNotAllowedException;
use Neat\Http\Exception\RouteNotFoundException;

/**
 * Router
 */
class Router
{
    /** @var string|null */
    private $segment;

    /** @var string */
    private $name;

    /** @var string */
    private $expression;

    /** @var static[] */
    private $literals = [];

    /** @var static[] */
    private $variables = [];

    /** @var static|null */
    private $wildcard;

    /** @var static|null */
    private $variadic;

    /** @var callable[] */
    private $methodHandler = [];

    /** @var array */
    private $methodMiddleware = [];

    /** @var array */
    private $middleware = [];

    /**
     * Router constructor
     *
     * @param string $segment
     */
    public function __construct(string $segment = null)
    {
        $this->segment = $segment;
        if (!$segment) {
            return;
        }
        if (strpos($segment, '...$') === 0) {
            $this->name = substr($segment, 4);
        }
        if (preg_match('/^\$([^:]+)(?::(.*))?$/', $segment, $match)) {
            $this->name = $match[1];
            $this->expression = isset($match[2]) ? "/^$match[2]$/" : null;
        }
    }

    /**
     * Is variable segment?
     *
     * @return bool
     */
    private function isVariable(): bool
    {
        return $this->segment && $this->segment[0] == '$';
    }

    /**
     * Is variadic segment?
     *
     * @return bool
     */
    private function isVariadic(): bool
    {
        return $this->segment && strpos($this->segment, '...$') === 0;
    }

    /**
     * Is wildcard segment?
     *
     * @return bool
     */
    private function isWildcard(): bool
    {
        return $this->segment == '*';
    }

    /**
     * Add GET route
     *
     * @param string   $url
     * @param callable $handler
     * @param array    $middleware
     */
    public function get(string $url, $handler, ...$middleware)
    {
        $this->map($this->split($url))->method('GET', $handler, $middleware);
    }

    /**
     * Add POST route
     *
     * @param string   $url
     * @param callable $handler
     * @param array    $middleware
     */
    public function post(string $url, $handler, ...$middleware)
    {
        $this->map($this->split($url))->method('POST', $handler, $middleware);
    }

    /**
     * Add PUT route
     *
     * @param string   $url
     * @param callable $handler
     * @param array    $middleware
     */
    public function put(string $url, $handler, ...$middleware)
    {
        $this->map($this->split($url))->method('PUT', $handler, $middleware);
    }

    /**
     * Add PATCH route
     *
     * @param string   $url
     * @param callable $handler
     * @param array    $middleware
     */
    public function patch(string $url, $handler, ...$middleware)
    {
        $this->map($this->split($url))->method('PATCH', $handler, $middleware);
    }

    /**
     * Add DELETE route
     *
     * @param string   $url
     * @param callable $handler
     * @param array    $middleware
     */
    public function delete(string $url, $handler, ...$middleware)
    {
        $this->map($this->split($url))->method('DELETE', $handler, $middleware);
    }

    /**
     * Add a controller route
     *
     * @param string   $url
     * @param callable $handler
     * @param array    $middleware
     */
    public function any(string $url, $handler, ...$middleware)
    {
        $this->map($this->split($url))->method('ANY', $handler, $middleware);
    }

    /**
     * Get a sub-router
     *
     * @param string $url
     * @param array  $middleware
     * @return static
     */
    public function in(string $url, ...$middleware): self
    {
        $router = $this->map($this->split($url));
        $router->middleware = array_merge($this->middleware, $middleware);

        return $router;
    }

    /**
     * Split a path into segments
     *
     * @param string $path
     * @return array
     */
    private function split(string $path): array
    {
        return array_filter(explode('/', $path));
    }

    /**
     * Map path segments
     *
     * @param array $segments
     * @return static
     */
    private function map(array $segments): self
    {
        if (!$segment = array_shift($segments)) {
            return $this;
        }

        $map = $this->literals[$segment]
            ?? $this->variables[$segment]
            ?? (strpos($segment, '...$') === 0  ? $this->variadic : null)
            ?? ($segment == '*' ? $this->wildcard : null);

        if (!$map) {
            $map = new static($segment);
            if ($map->isWildcard()) {
                $this->wildcard = $map;
            } elseif ($map->isVariable()) {
                $this->variables[$segment] = $map;
            } elseif ($map->isVariadic()) {
                $this->variadic = $map;
            } else {
                $this->literals[$segment] = $map;
            }
        }

        return $map->map($segments);
    }

    /**
     * Set method handler and middleware
     *
     * @param string   $method
     * @param callable $handler
     * @param array    $middleware
     */
    private function method(string $method, $handler, $middleware)
    {
        $this->methodHandler[$method]    = $handler;
        $this->methodMiddleware[$method] = $middleware;
    }

    /**
     * Match path
     *
     * @param array $segments
     * @param array $arguments
     * @param array $middleware
     * @return static|null
     */
    private function matchPath(array $segments, &$arguments = [], &$middleware = [])
    {
        if (!$segments) {
            return $this;
        }

        $segment = array_shift($segments);
        if ($literal = $this->literals[$segment] ?? null) {
            $match = $literal->matchPath($segments, $arguments, $middleware);
            if ($match && $match->methodHandler) {
                array_splice($middleware, 0, 0, $literal->middleware);

                return $match;
            }
        }
        foreach ($this->variables as $variable) {
            if ($variable->expression && !preg_match($variable->expression, $segment)) {
                continue;
            }
            $match = $variable->matchPath($segments, $arguments, $middleware);
            if ($match && $match->methodHandler) {
                $arguments[$variable->name] = $segment;
                array_splice($middleware, 0, 0, $variable->middleware);

                return $match;
            }
        }
        if ($this->variadic && $this->variadic->methodHandler) {
            array_unshift($segments, $segment);
            $arguments[$this->variadic->name] = $segments;
            array_splice($middleware, 0, 0, $this->variadic->middleware);

            return $this->variadic;
        }
        if ($this->wildcard && $this->wildcard->methodHandler) {
            array_unshift($segments, $segment);
            $arguments = $segments;
            array_splice($middleware, 0, 0, $this->wildcard->middleware);

            return $this->wildcard;
        }

        return null;
    }

    /**
     * Match method
     *
     * @param string $method
     * @param array  $middleware
     * @return callable|null
     */
    private function matchMethod(string $method, array &$middleware)
    {
        $methods = $method === 'HEAD' ? ['HEAD', 'GET', 'ANY'] : [$method, 'ANY'];
        foreach ($methods as $method) {
            if ($handler = $this->methodHandler[$method] ?? null) {
                $middleware = array_merge($middleware, $this->methodMiddleware[$method] ?? []);

                return $handler;
            }
        }

        return null;
    }

    /**
     * Route a request and return the handler
     *
     * @param string $method
     * @param string $path
     * @param array  $arguments
     * @param array  $middleware
     * @return callable
     */
    public function match(string $method, string $path, array &$arguments = null, array &$middleware = null)
    {
        $arguments = [];
        $middleware = $this->middleware;

        $map = $this->matchPath($this->split($path), $arguments, $middleware);
        if (!$map) {
            throw new RouteNotFoundException('Route not found');
        }

        $handler = $map->matchMethod(strtoupper($method), $middleware);
        if (!$handler) {
            throw new MethodNotAllowedException('Method not allowed');
        }

        return $handler;
    }
}
