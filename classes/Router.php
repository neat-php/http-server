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

    /** @var Router[] */
    private $literals = [];

    /** @var Router[] */
    private $variables = [];

    /** @var Router|null */
    private $wildcard;

    /** @var string */
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

        if ($segment && preg_match('/^\$([^:]+)(?::(.*))?$/', $segment, $match)) {
            $this->name       = $match[1];
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
     * @return Router
     */
    public function in(string $url, ...$middleware): Router
    {
        $router = $this->map($this->split($url));
        $router->middleware = $middleware;

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
     * @return Router
     */
    private function map(array $segments): Router
    {
        if (!$segment = array_shift($segments)) {
            return $this;
        }
        if (strpos($segment, '...$') === 0) {
            $this->variadic = substr($segment, 4);

            return $this;
        }

        $map = $this->literals[$segment]
            ?? $this->variables[$segment]
            ?? ($segment == '*' ? $this->wildcard : null);

        if (!$map) {
            $map = new Router($segment);
            if ($map->isWildcard()) {
                $this->wildcard = $map;
            } elseif ($map->isVariable()) {
                $this->variables[$segment] = $map;
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
     * @return Router|null
     */
    private function matchPath(array $segments, &$arguments = [], &$middleware = [])
    {
        if (!$segments) {
            if ($this->variadic) {
                $arguments[$this->variadic] = [];
            }

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
        if ($this->variadic && $this->methodHandler) {
            array_unshift($segments, $segment);
            $arguments[$this->variadic] = $segments;

            return $this;
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
        $middleware = array_merge(
            $middleware,
            $this->methodMiddleware[$method] ?? $this->methodMiddleware['ANY'] ?? []
        );

        return $this->methodHandler[$method]
            ?? $this->methodHandler['ANY']
            ?? null;
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
        $arguments  = [];
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
