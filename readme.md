Neat HTTP Server components
===========================
[![Stable Version](https://poser.pugx.org/neat/http-server/version)](https://packagist.org/packages/neat/http-server)
[![Build Status](https://travis-ci.org/neat-php/http-server.svg?branch=master)](https://travis-ci.org/neat-php/http-server)

Neat HTTP server components provide a clean and expressive API for your
application to receive HTTP requests and send HTTP responses.

Requirements
------------
To use Neat HTTP Server components you will need
- PHP 7.0 or newer
- a [PSR-7 HTTP message implementation](https://packagist.org/providers/psr/http-message-implementation)
- a [PSR-17 HTTP factory implementation](https://packagist.org/providers/psr/http-factory-implementation)

Getting started
---------------
To install this package, simply issue [composer](https://getcomposer.org) on the
command line:
```
composer require neat/http-server
```

Server
------
```php
<?php

// Create a PSR-17 factory
$factory = new Example\Factory();

// Then create the server using this factory (three interfaces are required)
$server = new Neat\Http\Server\Server($factory, $factory, $factory);

// Write a handler to handle incoming requests
$handler = new Neat\Http\Server\Handler\CallableHandler(function (Neat\Http\ServerRequest $request) {
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
<?php /** @noinspection PhpInconsistentReturnPointsInspection */

// Write a handler from scratch
class Handler implements Neat\Http\Server\Handler
{
    public function handle(Neat\Http\ServerRequest $request): Neat\Http\Response
    {
        // return new Neat\Http\Response(...);
    }
}

// Alternatively write a handler using a closure
$handler = new Neat\Http\Server\Handler\CallableHandler(function (Neat\Http\ServerRequest $request) {
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
    public function process(Neat\Http\ServerRequest $request, Neat\Http\Server\Handler $handler): Neat\Http\Response
    {
        return $handler->handle($request);
    }
}

// Or using a closure
$handler = new Neat\Http\Server\Middleware\CallableMiddleware(
function (Neat\Http\ServerRequest $request, Neat\Http\Server\Handler $handler) {
    return $handler->handle($request);
});
```

Dispatcher
----------
To use your middleware when handling the request, you can use the Dispatcher.

```php
<?php

// Assuming we have a handler readily available, we can create a Dispatcher
// with a stack of one or more Middleware instances
/** @var Neat\Http\Server\Handler $handler */
$dispatcher = new Neat\Http\Server\Dispatcher(
    $handler,
    new Neat\Http\Server\Middleware\CallableMiddleware(function () { /* ... */ }),
    new Neat\Http\Server\Middleware\CallableMiddleware(function () { /* ... */ })
);

// Then using the request we can ask the dispatcher to handle the request and
// return the response from the handler through the middleware.
/** @var Neat\Http\ServerRequest $request */
$response = $dispatcher->handle($request);
```

Output
------
Creating responses from your controllers is real easy
```php
<?php

// First create the output helper using a PSR-17 factory
$factory = new Example\Factory();
$output  = new Neat\Http\Server\Output($factory, $factory);

// Then create a simple text response (it will have the proper Content-Type header set)
$response = $output->text('Hello world!');

// Or an html response (with the text/html Content-Type header)
$response = $output->html('<html lang="en"><body>Hi!</body></html>');

// Change or add headers with any Neat\Http\Response
$response = $output->html('{key:"value"}')->withContentType('application/json');

// Or just let the output create a JSON response directly
$response = $output->json(['key' => 'value']);

// Rendering a view is just as easy using the output helper
$response = $output->view('template', ['message' => 'Hello world!']);

// Download a file
$response = $output->download(fopen('path/to/really/large/file.bin', 'r+'));

// Display it inline
$response = $output->display('path/to/file.pdf');

// Other types of responses
$response = $output->response(404, "These aren't the pages you're looking for.");
```

Redirect
--------
Redirecting a client to another URL is easy using the redirect output helper.
```php
<?php
/** @var Neat\Http\Server\Input $input */
/** @var Neat\Http\Server\Output $output */
/** @var Neat\Http\ServerRequest $request */

// Redirect to a url
$response = $output->redirect()->to('/go/there/instead');

// Redirect permanently
$response = $output->redirect()->permanent()->to('/go/there/instead');

// Redirect and resubmit
$response = $output->redirect()->resubmit()->to('/submit/there/instead');

// Redirect back to the referring url
$response = $output->redirect()->back($request);

// Refresh
$response = $output->redirect()->refresh($request);

// Retry input
$response = $output->redirect()->retry($input);
```


In your handler you can use the output helper to convert any return value other
than a Neat\Http\Response into one.
```php
$factory = new Example\Factory();
$output  = new Neat\Http\Server\Output($factory, $factory);

// By default any value that isn't a Neat\Http\Response will be converted to a JSON response  
$response = $output->resolve(['What now?' => 'My controller just returned this lousy array.']);

// You can learn the output to handle any type using a Response factory
$output->register('string', function (string $string) use ($output) {
    return $output->html($string);
});
$response = $output->resolve('HELP my controller just returned a string!');

// Null could be your way of returning a 204 No content response
$output->register('null', function () use ($output) {
    return $output->response(204);
});

// If you want objects with a __toString method to convert differently
$output->register('object', function ($object) use ($output) {
    if (method_exists($object, '__toString')) {
        return (string) $object;
    }

    return $output->json($object);
});

// You can even target specific classes or interfaces
$output->register(Psr\Http\Message\StreamInterface::class, function (Psr\Http\Message\StreamInterface $stream) use ($output) {
    return $output->response()->withBody($stream);
});
```
