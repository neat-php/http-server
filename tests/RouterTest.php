<?php

namespace Neat\Http\Server\Test;

use Exception;
use Neat\Http\Exception\MethodNotAllowedException;
use Neat\Http\Exception\RouteNotFoundException;
use Neat\Http\Server\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testIn()
    {
        $router = new Router();
        $group  = $router->in('/test');
        $this->assertNotSame($router, $group);
    }

    private function router(): Router
    {
        $router = new Router();
        $router->get('/test', 'test');
        $router->get('/test/$id:\d+', 'test-id-number');
        $router->get('/test/$id:\w+', 'test-id-word');
        $router->get('/test/test', 'get-test-test');
        $router->post('/test/test', 'post-test-test');
        $router->put('/test/test', 'put-test-test');
        $router->patch('/test/test', 'patch-test-test');
        $router->delete('/test/test', 'delete-test-test');
        $router->get('/arg/*', 'test-arg');
        $router->any('/any', 'any-test');
        $router->get('/variadic/...$variadic', 'variadic-test-get');
        $router->post('/variadic/...$variadic', 'variadic-test-post');

        return $router;
    }

    public function testAll()
    {
        $router = $this->router();

        $this->assertSame('test', $router->match('GET', '/test'));
        $this->assertSame('test-id-number', $router->match('GET', '/test/5', $parameters));
        $this->assertSame(['id' => '5'], $parameters);
        $this->assertSame('test-id-word', $router->match('GET', '/test/hello', $parameters));
        $this->assertSame(['id' => 'hello'], $parameters);
        $this->assertSame('get-test-test', $router->match('GET', '/test/test'));
        $this->assertSame('post-test-test', $router->match('POST', '/test/test'));
        $this->assertSame('put-test-test', $router->match('PUT', '/test/test'));
        $this->assertSame('patch-test-test', $router->match('PATCH', '/test/test'));
        $this->assertSame('delete-test-test', $router->match('DELETE', '/test/test'));
        $this->assertSame('test-arg', $router->match('GET', '/arg/bla/5', $parameters));
        $this->assertSame(['bla', '5'], $parameters);
        $this->assertSame('test-arg', $router->match('GET', '/arg/bla/5/and/more', $parameters));
        $this->assertSame(['bla', '5', 'and', 'more'], $parameters);
        $this->assertSame('any-test', $router->match('GET', '/any'));
        $this->assertSame('any-test', $router->match('POST', '/any'));
        $this->assertSame('variadic-test-get', $router->match('GET', '/variadic/test'));
        $this->assertSame('variadic-test-post', $router->match('POST', '/variadic/test'));
    }

    public function testAnyVersusGet()
    {
        $router = new Router();
        $router->any('/', 'test-any');
        $router->get('/', 'test-get');

        $this->assertSame('test-get', $router->match('HEAD', '/'));
        $this->assertSame('test-get', $router->match('GET', '/'));
        $this->assertSame('test-any', $router->match('POST', '/'));
    }

    public function testGetVersusAny()
    {
        $router = new Router();
        $router->get('/', 'test-get');
        $router->any('/', 'test-any');

        $this->assertSame('test-get', $router->match('HEAD', '/'));
        $this->assertSame('test-get', $router->match('GET', '/'));
        $this->assertSame('test-any', $router->match('POST', '/'));
    }

    public function testVariadic()
    {
        $router = new Router();
        $router->get('/test', 'Test');
        $router->get('/test/...$all', 'TestVariadic');
        $router->get('/variadic/...$variadic', 'TestVariadicVariadic');
        $router->get('/...$all', 'RootVariadic');

        $this->assertSame('Test', $router->match('get', '/test', $arguments));
        $this->assertSame([], $arguments);

        $this->assertSame('TestVariadic', $router->match('get', '/test/first', $arguments));
        $this->assertSame(['all' => ['first']], $arguments);

        $this->assertSame('TestVariadic', $router->match('get', '/test/first/second', $arguments));
        $this->assertSame(['all' => ['first', 'second']], $arguments);

        $this->assertSame('RootVariadic', $router->match('get', '/root/first/second', $arguments));
        $this->assertSame(['all' => ['root', 'first', 'second']], $arguments);

        $router = new Router();
        $router->get('/variadic/...$variadic', 'TestVariadicVariadic');
        $this->expectExceptionObject(new RouteNotFoundException('Route not found'));
        $router->match('GET', '/variadic');
    }

    public function testWildcardVersusPartialMatch()
    {
        $router = new Router();
        $router->get('/partial/path', 'test-partial-path');
        $router->get('/*', 'test-wildcard');

        $this->assertSame('test-wildcard', $router->match('GET', '/partial/'));
        $this->assertSame('test-wildcard', $router->match('GET', '/partial'));
        $this->assertSame('test-wildcard', $router->match('GET', 'partial'));
    }

    public function testEmptyPathSegments()
    {
        $router = new Router();
        $router->get('/a/b', 'test-a-b');
        $router->get('/c//d', 'test-c-d');
        $router->get('e', 'test-e');
        $router->get('', 'test-get-root');
        $router->post('/', 'test-post-root');

        $this->assertSame('test-a-b', $router->match('GET', 'a/b'));
        $this->assertSame('test-a-b', $router->match('GET', '/a//b'));
        $this->assertSame('test-a-b', $router->match('GET', '//a/b'));
        $this->assertSame('test-a-b', $router->match('GET', '//a//b'));

        $this->assertSame('test-c-d', $router->match('GET', 'c/d'));
        $this->assertSame('test-c-d', $router->match('GET', '/c//d'));
        $this->assertSame('test-c-d', $router->match('GET', '//c/d'));
        $this->assertSame('test-c-d', $router->match('GET', '//c//d'));

        $this->assertSame('test-e', $router->match('GET', 'e'));
        $this->assertSame('test-e', $router->match('GET', '/e'));
        $this->assertSame('test-e', $router->match('GET', '//e'));

        $this->assertSame('test-get-root', $router->match('GET', ''));
        $this->assertSame('test-get-root', $router->match('GET', '/'));
        $this->assertSame('test-post-root', $router->match('POST', ''));
        $this->assertSame('test-post-root', $router->match('POST', '/'));
    }

    /**
     * Test middleware
     */
    public function testMiddleware()
    {
        $router = new Router();
        $router->get('/', 'HomeController');
        $router->get('/admin', 'AdminController', 'AuthenticationMiddleware');
        $router->get('/admin/firewall', 'AdminController', 'AuthenticationMiddleware', 'FirewallMiddleware');

        $router->match('GET', '/', $arguments, $middleware);
        $this->assertSame([], $middleware);

        $router->match('GET', '/admin', $arguments, $middleware);
        $this->assertSame(['AuthenticationMiddleware'], $middleware);

        $router->match('GET', '/', $arguments, $middleware);
        $this->assertSame([], $middleware);

        $router->match('GET', '/admin/firewall', $arguments, $middleware);
        $this->assertSame(['AuthenticationMiddleware', 'FirewallMiddleware'], $middleware);
    }

    /**
     * Test middleware
     */
    public function testRecursiveMiddleware()
    {
        $router = new Router();
        $router->get('/', 'HomeController');
        $router->in('/admin', 'AuthenticationMiddleware');
        $router->get('/admin', 'AdminController');
        $router->post('/admin', 'AdminController', 'CsrfMiddleware');

        $router->in('/admin/firewall', 'FirewallMiddleware');
        $router->get('/admin/firewall', 'AdminController');
        $router->post('/admin/firewall', 'AdminController', 'CsrfMiddleware');

        $router->match('GET', '/', $arguments, $middleware);
        $this->assertSame([], $middleware);

        $router->match('GET', '/admin', $arguments, $middleware);
        $this->assertSame(['AuthenticationMiddleware'], $middleware);

        $router->match('GET', '/', $arguments, $middleware);
        $this->assertSame([], $middleware);

        $router->match('POST', '/admin', $arguments, $middleware);
        $this->assertSame(['AuthenticationMiddleware', 'CsrfMiddleware'], $middleware);

        $router->match('GET', '/admin/firewall', $arguments, $middleware);
        $this->assertSame(['AuthenticationMiddleware', 'FirewallMiddleware'], $middleware);

        $arguments = null;
        $middleware = null;
        $router->match('POST', '/admin/firewall', $arguments, $middleware);
        $this->assertSame(['AuthenticationMiddleware', 'FirewallMiddleware', 'CsrfMiddleware'], $middleware);
    }

    /**
     * @return array
     */
    public function provideExceptionData(): array
    {
        return [
            [new RouteNotFoundException('Route not found'), 'GET', '/hello-world'],
            [new MethodNotAllowedException('Method not allowed'), 'POST', '/test'],
            [new RouteNotFoundException('Route not found'), 'GET', '/test/hello-world'],
        ];
    }

    /**
     * @dataProvider provideExceptionData
     * @param Exception $exception
     * @param string    $method
     * @param string    $path
     */
    public function testExceptions(Exception $exception, string $method, string $path)
    {
        $this->expectExceptionObject($exception);
        $this->router()->match($method, $path);
    }
}
