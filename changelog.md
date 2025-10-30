# Changelog
All notable changes to Neat HTTP Server components will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
## Fixed
- Routes containing 0 are incorrectly resolved.

## [0.3.1] - 2025-09-26
## Fixed
- Middleware type-hints.
- File uploads for PHP 8.1+.

## [0.3.0] - 2025-02-20
### Added
- Parameter-, property- & return type-hints.
- Constant visibility.

### Removed
- Redundant doc-blocks.
- Support for PHP 7.2 & 7.3.

### Fixed
- Implicit optional arguments.

## [0.2.3] - 2024-08-15
### Added
- Support for PHP 8.1.

## [0.2.2] - 2022-11-30
### Fixed
- Path middleware is being overwritten.

## [0.2.1] - 2022-10-27
### Fixed
- Undefined index notice on `Input->data`.

## [0.2.0] - 2022-10-11
### Added
- Missing type-hints for the Input class.

### Changed
- Input->filter() will call all filters even when the value is `null`, filters are required to be null-safe.
- Minimum PHP version is now 7.2.

## [0.1.8] - 2021-09-06
### Fixed
- Multi dimensional files array doesn't maintain structure #12.

### Added
- $output->body('content') method to create response messages without a given content type.
- $output->xml($document) method to create XML responses.
- Add Content-length header by default to responses created using Output methods (body, html, json, text and view).

## [0.1.7] - 2020-04-07
### Changed
- Required filter will be called even if the value is `null` #3.
- FilterNotFoundException will be thrown when an unregistered or non-existing function is passed as filter.
- InvalidArgumentException will be thrown when an invalid arguments is passed to the Input->filter() method.

## [0.1.6] - 2020-01-30
### Fixed
- Receive request hostname using SERVER_NAME when HTTP_HOST is missing from $_SERVER.
- Match HEAD requests like GET when using the Router.

## [0.1.5] - 2020-01-14
### Added
- Output sets Content-Length header for files.

## [0.1.4] - 2020-01-02
### Added
- Variadic routes like $router->any('/...$arguments', $handler);

## [0.1.3] - 2020-01-02
### Fixed
- Receiving file uploads with error.

## [0.1.2] - 2020-01-02
### Fixed
- Sending multiple header values.

## [0.1.1] - 2019-12-27
### Added
- Router middleware in an entire path (recursively).

## [0.1.0] - 2019-12-19
### Added
- Server implementation using [PSR-17](https://www.php-fig.org/psr/psr-17/) HTTP factories.
- Request implementation (ServerRequestInterface wrapper).
- Upload implementation (UploadedFileInterface wrapper).
- Middleware dispatcher.
- Middleware interface and implementations for closures and [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware.
- Handler interface implementation for closures and [PSR-15](https://www.php-fig.org/psr/psr-15/) handlers.
- Input, Session and Output helpers.
