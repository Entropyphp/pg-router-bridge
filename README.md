# pg-router-bridge


[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net)
[![Coverage Status](https://coveralls.io/repos/github/Entropyphp/pg-router-bridge/badge.svg?branch=main)](https://coveralls.io/github/Entropyphp/pg-router-bridge?branch=main)
[![Continuous Integration](https://github.com/Entropyphp/pg-router-bridge/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Entropyphp/pg-router-bridge/actions/workflows/ci.yml)

Bridge to integrate [willy68/pg-router](https://github.com/willy68/pg-router) with the Entropy framework.

## Installation

```bash
composer require entropyphp/pg-router-bridge
```

The package will be automatically installed as a module thanks to `entropyphp/module-installer`.

## Configuration

The package provides several classes to integrate the router:

- `RouterFactory`: Factory to create the router instance
- `RouterConfigFactory`: Factory for router configuration
- `RouterMiddleware`: Middleware to handle routing
- `RouterListener`: Listener to handle routing
- `MethodNotAllowedMiddleware`: Middleware to handle 405 errors
- `MethodNotAllowedListener`: Listener to handle 405 errors in event mode
- `RouteListCommand`: CLI command to list all routes

## Usage

### CLI Commands

List all registered routes:
```bash
php bin/console route:list
```

## Module Features

This package is a PG module that:
- Auto-registers in the application using `entropyphp/module-installer`
- Provides CLI commands through Symfony Console
- Integrates with the Entropy framework's event system
- Supports PSR-15 middleware

## Tests

```bash
composer run tests
```

## License

MIT
