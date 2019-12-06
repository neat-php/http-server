Neat HTTP Server components
===========================
[![Stable Version](https://poser.pugx.org/neat/http-server/version)](https://packagist.org/packages/neat/http-server)
[![Build Status](https://travis-ci.org/neat-php/http-server.svg?branch=master)](https://travis-ci.org/neat-php/http-server)

Neat HTTP server components provide a clean and expressive API for your
application to receive HTTP requests and send HTTP responses.

Getting started
---------------
To install this package, simply issue [composer](https://getcomposer.org) on the
command line:
```
composer require neat/http-server
```

Additionally, you will need
a [PSR-7 HTTP message implementation](https://packagist.org/providers/psr/http-message-implementation)
and a [PSR-17 HTTP factory implementation](https://packagist.org/providers/psr/http-factory-implementation)
to use Neat HTTP components.

Server
------
```php
<?php

// Create a PSR-17 factory (for example using nyholm/psr7)
$factory = new Nyholm\Psr7\Factory\Psr17Factory();

// Then create the server using this factory (three interfaces are required)
$server = new Neat\Http\Server\Server($factory, $factory, $factory);

// Write a handler to handle incoming requests
$handler = new Neat\Http\Server\Handler\CallableHandler(function (Neat\Http\Request $request) {
    // return new Neat\Http\Response(...);
});
```

Then use the server to receive the request, handle the request and send the response back:
```php
<?php

/** @var Neat\Http\Server\Server $server */
/** @var Neat\Http\Server\Handler $handler */

// Receive the request
$request = $server->receive();

// Handle the request
$response = $handler->handle($request);

// Send the response
$server->send($response);
```

Handlers
--------
Handlers can be written from scratch using the Handler interface or created
using one of the provided adapters:
```php
<?php

// Write a handler from scratch
class Handler implements Neat\Http\Server\Handler
{
    public function handle(Neat\Http\Request $request): Neat\Http\Response
    {
        return new Neat\Http\Response(new Nyholm\Psr7\Response('Hello world!'));
    }
}

// Alternatively write a handler using a closure
$handler = new Neat\Http\Server\Handler\CallableHandler(function (Neat\Http\Request $request) {
    // return new Neat\Http\Response(...);
});

// Or use an existing PSR-15 RequestHandlerInterface implementation
/** @var Psr\Http\Server\RequestHandlerInterface $psr */
$handler = new Neat\Http\Server\Handler\PsrHandler($psr);
```

Middleware
----------
To intercept incoming requests, outgoing responses and possibly exceptions,
you can create a Middleware that adds an extra layer of control over your
handler and the messages going in and out.
```php
<?php

// Write a middleware from scratch
class Middleware implements Neat\Http\Server\Middleware
{
    public function process(Neat\Http\Request $request, Neat\Http\Server\Handler $handler): Neat\Http\Response
    {
        return $handler->handle($request);
    }
}

// Or using a closure
$handler = new Neat\Http\Server\Middleware\CallableMiddleware(
function (Neat\Http\Request $request, Neat\Http\Server\Handler $handler) {
    return $handler->handle($request);
});

```

Dispatcher
----------
To use t

```php
<?php

/** @var Neat\Http\Server\Handler $handler */
$dispatcher = new Neat\Http\Server\Dispatcher(
    $handler,
    new Neat\Http\Server\Middleware\CallableMiddleware(function () { /* ... */ }),
    new Neat\Http\Server\Middleware\CallableMiddleware(function () { /* ... */ })
);

/** @var Neat\Http\Request $request */
$response = $dispatcher->handle($request);
```

Responding
----------
Building responses with Neat HTTP components is real easy:
```php
<?php

// Create a simple text response
$response = new Neat\Http\Response('Hello world!');

// Add a header
$response = $response->withHeader('Content-Type', 'text/plain');

// Use a non-200 status code
$response = $response->withStatus(403);
```

Certain types of responses (redirects for example) can become quite cumbersome
to create. Therefor you can use static factory methods like ```redirect```: 
```php
<?php /** @var Neat\Http\Response $response */

// Create a redirection response
$response = Neat\Http\Response::redirect('/');

// Or create a 204 No Content response using the normal constructor
$response = new Neat\Http\Response(null);
```
