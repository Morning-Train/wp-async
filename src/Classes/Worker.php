<?php namespace Morningtrain\WP\Async\Classes;

use Morningtrain\WP\Core\Abstracts\AbstractSingleton;
use Morningtrain\WP\Async\Abstracts\AbstractAsyncTask;
use WP_REST_Request;
use WP_Error;

class Worker {

    private static ?Worker $instance = null;

    public static function register() :static
    {
        $worker = static::getInstance();

        add_action('rest_api_init', [static::class, 'registerRestRoute']);

        return $worker;
    }

    public static function getInstance() :Worker
    {
        if (static::$instance === null) {
            static::$instance = new Worker();
        }

        return static::$instance;
    }

    public static function registerRestRoute() :void
    {
        register_rest_route(
            'morningtrain/wp-async',
            'run-task',
            array(
                'methods' => 'POST',
                'permission_callback' => [static::class, 'verifyRequest'],
                'callback' => [static::class, 'handle']
            )
        );
    }

    public static function verifyRequest(WP_REST_Request $request) :bool
    {
        $referer = strtolower(wp_get_referer());
        $site_url = strtolower(site_url());

        if (strpos($referer, $site_url) === false) {
            return false;
        }

        $class = $request->get_param('class');
        $data = (array) json_decode($request->get_param('data'), true) ?? [];
        $request_nonce = $request->get_param('request_nonce');

        if (!is_subclass_of($class, AbstractAsyncTask::class)) {
            return false;
        }

        if(!method_exists($class, 'getCallback')) {
            return false;
        }

        return (bool) static::verifyNonce($request_nonce, $class, $data);
    }

    protected static function getNonceAction(string $class, array $data) :string
    {
        return md5(json_encode($class), json_encode($data));
    }

    protected static function createNonce(string $class, array $data) :string
    {
        $action = static::getNonceAction($class, $data);
        $i      = wp_nonce_tick();

        return substr( wp_hash( $i . '|' . $action, 'nonce' ), - 12, 10 );
    }

    protected static function verifyNonce(string $nonce, string $class, array $data) :bool|int
    {
        $action = static::getNonceAction($class, $data);
        $i      = wp_nonce_tick();

        // Nonce generated 0-12 hours ago
        if ( substr( wp_hash( $i . '|' . $action, 'nonce' ), - 12, 10 ) == $nonce ) {
            return 1;
        }

        // Nonce generated 12-24 hours ago
        if ( substr( wp_hash( ( $i - 1 ) . '|' . $action, 'nonce' ), - 12, 10 ) == $nonce ) {
            return 2;
        }

        // Invalid nonce
        return false;
    }

    public function dispatchAsyncTask(string $class, array $data = []) :array|WP_Error
    {
        return $this->dispatchTask($class, $data, 0.01, false);
    }

    public function dispatchBlockingTask(string $class, array $data = [], $timeout = 5) :array|WP_Error
    {
        return $this->dispatchTask($class, $data, $timeout, true);
    }

    protected function dispatchTask(string $class, array $data = [], $timeout = 0.01, $blocking = false) : array|WP_Error
    {
        $abstract_class_name = AbstractAsyncTask::class;

        if (!is_subclass_of($class, $abstract_class_name)) {
            return new WP_Error('invalid_class', __("The class must be a subclass of {$abstract_class_name}", 'wp-async'));
        }

        if(!method_exists($class, 'getCallback')) {
            return new WP_Error('invalid_callback', __('Invalid callback', 'wp-async'));
        }

        return wp_remote_post(rest_url('morningtrain/wp-async/run-task'), [
            'timeout' => $timeout,
            'blocking' => $blocking,
            'body' => [
                'class' => $class,
                'data' => json_encode($data),
                'request_nonce' => static::createNonce($class, $data)
            ],
            'headers' => [
                'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                'Referer' => strtolower(site_url()),
            ],
            'cookies' => $_COOKIE,
            'sslverify' => apply_filters('https_local_ssl_verify', false),
        ]);
    }

    public static function handle(WP_REST_Request $request) :void
    {
        $class = $request->get_param('class');
        $data = (array) json_decode($request->get_param('data'), true) ?? [];

        static::runTask($class, $data);
    }

    protected static function runTask(?string $class , array $data = []) :void
    {
        if(!method_exists($class, 'getCallback')) {
            wp_send_json_error(new WP_Error('invalid_callback', __('Invalid callback', 'wp-async')));
        }

        $result = call_user_func_array($class::getCallback(), $data);

        if (is_wp_error($result)) {
            wp_send_json_error($result);
            exit;
        }

        wp_send_json_success($result);
    }

}
