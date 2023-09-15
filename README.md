# Morningtrain\WP\Async

Async task handler for WordPress

## Table of Contents

- [Introduction](#introduction)
- [Getting Started](#getting-started)
    - [Installation](#installation)
- [Usage](#usage)
    - [Register Worker](#register-worker)
    - [Create a Task](#create-a-task)
    - [Dispatch Async Task](#dispatch-async-task)
    - [Dispatch Blocking Task](#dispatch-blocking-task)
- [Contributing](#contributing)
- [Contributors](#contributors)
- [Testing](#testing)
- [License](#license)

## Introduction

This package is made to dispatch task asyncronely to a new thread.

## Getting started

To get started install the package as described below in [Installation](#installation).

To use the package have a look at [Usage](#usage)

### Installation

Install with composer.

```composer require morningtrain/wp-async```

## Usage

### Register Worker

To get started with the module simply register a worker `\Morningtrain\WP\Async\Async::registerWorker()`.

```php
\Morningtrain\WP\Async\Async::registerWorker();
```

### Create a Task
Jobs can be created by extending `Morningtrain\WP\Async\Abstracts\AbstractAsyncTask` and create a static `handle` method.

```php
use Morningtrain\WP\Async\Abstracts\AbstractAsyncTask;

class TestTask extends AbstractAsyncTask {
    public static function handle($arg1, $arg2) {
        // Do something;
        return "$arg1 $arg2";
    }
}
```

### Dispatch Async Task
You can dispatch a async task by caling the static method `dispatch` on your task class.

This will run the task asyncronely whitout waiting for response.

```php
TestTask::dispatch('arg1', 'arg2');
```

### Dispatch Blocking Task
You can dispatch a blocking task by caling the static method `dispatchBlocking` on your task class.

This will run the task in a new thread, and wait for response. 

```php
TestTask::dispatchBlocking('arg1', 'arg2');
```

#### Timeout

There will be a timout after 5 seconds on blocking task. 
If you need more time to handle your blocking task, you should overwrite the `dispatchBlocking` method on your task class.
You can call the `dispatchBlockingTask` method on the worker with timeout in second as third parameter.

```php
public static function dispatchBlocking(mixed ...$params) :array|WP_Error
{
    return static::getWorker()->dispatchBlockingTask(static::getCallback(), $params, 30);
}
```

#### Error handling

You can return a `WP_Error` object from your task, and it will be returned as status 400 with the wp error info.

```php
use Morningtrain\WP\Async\Abstracts\AbstractAsyncTask;

class TestTask extends AbstractAsyncTask {
    public static function handle($arg1, $arg2) {
        // Do something;
        
        $somethingWentWrong = true;
        
        if ($somethingWentWrong) {
            return new \WP_Error('something_went_wrong', 'Something went wrong');
        }
        
        return "$arg1 $arg2";
    }
}
```

You can also throw a Throwable (Exception), and it will be returned as status 500 with the exception message.

```php
use Morningtrain\WP\Async\Abstracts\AbstractAsyncTask;
use Exception;

class TestTask extends AbstractAsyncTask {
    public static function handle($arg1, $arg2) {
        // Do something;
        
        $somethingWentWrong = true;
        
        if ($somethingWentWrong) {
            throw new Exception('Something went wrong');
        }
        
        return "$arg1 $arg2";
    }
}
```

Alternatively you can return your own json response, if you need another response code.

```php
use Morningtrain\WP\Async\Abstracts\AbstractAsyncTask;

class TestTask extends AbstractAsyncTask {
    public static function handle($arg1, $arg2) {        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You are not allowed to do this!', 401);
            exit;
        }
        
        // Do something;
        
        return "$arg1 $arg2";
    }
}
```

## Contributing

Thank you for your interest in contributing to the project.

### Bug Report

If you found a bug, we encourage you to make a pull request.

To add a bug report, create a new issue. Please remember to add a telling title, detailed description and how to reproduce the problem.

### Support Questions

We do not provide support for this package.

### Pull Requests

1. Fork the Project
2. Create your Feature Branch (git checkout -b feature/AmazingFeature)
3. Commit your Changes (git commit -m 'Add some AmazingFeature')
4. Push to the Branch (git push origin feature/AmazingFeature)
5. Open a Pull Request

## Contributors

- [Martin Schadegg Brønniche](https://github.com/mschadegg)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.


---

<div align="center">
Developed by <br>
</div>
<br>
<div align="center">
<a href="https://morningtrain.dk" target="_blank">
<img src="https://morningtrain.dk/wp-content/themes/mtt-wordpress-theme/assets/img/logo-only-text.svg" width="200" alt="Morningtrain logo">
</a>
</div>
