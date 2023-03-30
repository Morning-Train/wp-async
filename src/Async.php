<?php namespace Morningtrain\WP\Async;

use Morningtrain\WP\Async\Classes\Worker;

class Async {
    public static function registerWorker() : Worker
    {
        return Worker::register();
    }
}