<?php

namespace Neat\Http\Server;

use Neat\Http\Exception\MethodNotAllowedException;
use Neat\Http\Exception\RouteNotFoundException;

/**
 * Router
 */
class Router
{
    private ?string $segment;
    private ?string $name = null;
    private ?string $expression = null;
    /** @var Router[] */
    private array $literals = [];
    /** @var Router[] */
    private array $variables = [];
    private ?Router $wildcard = null;
    private ?string $variadic = null;
    /** @var array<callable|string> */
    private array $methodHandler = [];
    /** @var array<callable|string> */
    private array $methodMiddleware = [];
    /** @var array<callable|string> */
    private array $middleware = [];

    public function __construct(?string $segment = null)
    {
        $this->segment = $segment;

        if ($segment && preg_match('/^\$([^:]+)(?::(.*))?$/', $segment, $match)) {
            $this->name = $match[1];
            $this->expression = isset($match[2]) ? "/^$match[2]$/" : null;
        }
    }

    private function isVariable(): bool
    {
        return $this->segment && $this->segment[0] == '$';
    }

    private function isWildcard(): bool
    {
        return $this->segment == '*';
    }

    /**
     * Add GET route
     *
     * @param callable|string $handler
     * @param callable|string $middleware
     */
    public function get(string $url, $handler, ...$middleware): void
    {
        $this->map($this->split($url))->method('GET', $handler, $middleware);
    }

    /**
     * Add POST route
     *
     * @param callable|string $handler
     * @param callable|string $middleware
     */
    public function post(string $url, $handler, ...$middleware): void
    {
        $this->map($this->split($url))->method('POST', $handler, $middleware);
    }

    /**
     * Add PUT route
     *
     * @param callable|string $handler
     * @param callable|string $middleware
     */
    public function put(string $url, $handler, ...$middleware): void
    {
        $this->map($this->split($url))->method('PUT', $handler, $middleware);
    }

    /**
     * Add PATCH route
     *
     * @param callable|string $handler
     * @param callable|string $middleware
     */
    public function patch(string $url, $handler, ...$middleware): void
    {
        $this->map($this->split($url))->method('PATCH', $handler, $middleware);
    }

    /**
     * Add DELETE route
     *
     * @param callable|string $handler
     * @param callable|string $middleware
     */
    public function delete(string $url, $handler, ...$middleware): void
    {
        $this->map($this->split($url))->method('DELETE', $handler, $middleware);
    }

    /**
     * Add a controller route
     *
     * @param callable|string $handler
     * @param callable|string $middleware
     */
    public function any(string $url, $handler, ...$middleware): void
    {
        $this->map($this->split($url))->method('ANY', $handler, $middleware);
    }

    /**
     * Get a sub-router
     *
     * @param string $url
     * @param callable|string $middleware
     * @return Router
     */
    public function in(string $url, ...$middleware): Router
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
     * @param callable|string $handler
     * @param array<callable|string> $middleware
     */
    private function method(string $method, $handler, array $middleware): void
    {
        $this->methodHandler[$method] = $handler;
        $this->methodMiddleware[$method] = $middleware;
    }

    /**
     * Match path
     *
     * @param list<string> $segments
     * @param array<string, string>|list<string> $arguments
     * @param array<callable|string> $middleware
     */
    private function matchPath(array $segments, array &$arguments = [], array &$middleware = []): ?self
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
     * @param array<callable|string> $middleware
     * @return callable|null|string
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
     * @return callable|string
     */
    public function match(string $method, string $path, ?array &$arguments = null, ?array &$middleware = null)
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
