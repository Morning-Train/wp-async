<?php namespace Morningtrain\WP\Async\Abstracts;

use Morningtrain\WP\Async\Classes\Worker;
use WP_Error;

abstract class AbstractAsyncTask {

    public static function getCallback() :callable
    {
        return [static::class, 'handle'];
    }

    protected static function getWorker() :Worker
    {
        return Worker::getInstance();
    }

    protected static function getCalledClass() :string
    {
        return get_called_class();
    }

    /**
     * Dispatch a task
     * @param mixed $data
     */
    public static function dispatch(mixed ...$params) :array|WP_Error
    {
        return static::getWorker()->dispatchAsyncTask(static::getCalledClass(), $params);
    }

    /**
     * Dispatch a task and wait for response
     * @return mixed
     */
    public static function dispatchBlocking(mixed ...$params) :array|WP_Error
    {
        return static::getWorker()->dispatchBlockingTask(static::getCalledClass(), $params);
    }
}