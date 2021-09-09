<?php

namespace TheCoder\Performance;

use Illuminate\View\View;
use \Illuminate\Contracts\Foundation\Application;
use \Illuminate\Database\Events\QueryExecuted;


class PerformanceServiceProvider extends \Illuminate\Support\ServiceProvider
{

    /**
     * {@inheritdoc}
     */
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
//        if ($this->app->runningInConsole()) {
//            $this->publishes([
//                __DIR__ . '/../config/config.php' => config_path('apiVersioning.php'),
//            ], 'config');
//        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {

        // record view
        $this->app['events']->listen($this->options['events'] ?? 'composing:*', [$this, 'recordView']);

        // record request
        $this->app['events']->listen(RequestHandled::class, [$this, 'recordRequest']);

        // record query
        $this->app['events']->listen(QueryExecuted::class, [$this, 'recordQuery']);

        //

        /** @var Router $router */
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', MyPackage\Middleware\WebOne::class);
        $this->app['router']->middleware('middlewareName', 'your\namespace\MiddlewareClass');

        // listen to database queries
        $this->app['db']->listen(function () {

        });
    }

    public function recordView($event, $data)
    {
        /** @var View $view */
        $view = $data[0];

        $data = [
            'name' => $view->getName(),
            'path' => $this->extractPath($view),
            'data' => $this->extractKeysFromData($view),
            'composers' => $this->formatComposers($view),
        ];
    }

    public function recordRequest(RequestHandled $event)
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');

        $data = [
            'ip_address' => $event->request->ip(),
            'uri' => str_replace($event->request->root(), '', $event->request->fullUrl()) ?: '/',
            'method' => $event->request->method(),
            'controller_action' => optional($event->request->route())->getActionName(),
            'middleware' => array_values(optional($event->request->route())->gatherMiddleware() ?? []),
            'headers' => $this->headers($event->request->headers->all()),
            'payload' => $this->payload($this->input($event->request)),
            'session' => $this->payload($this->sessionVariables($event->request)),
            'response_status' => $event->response->getStatusCode(),
            'response' => $this->response($event->response),
            'duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
            'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
        ];
    }

    public function recordQuery(QueryExecuted $event)
    {

        $time = $event->time;

//        if ($caller = $this->getCallerFromStackTrace()) {
//            $data = [
//                'connection' => $event->connectionName,
//                'bindings' => [],
//                'sql' => $this->replaceBindings($event),
//                'time' => number_format($time, 2, '.', ''),
//                'slow' => isset($this->options['slow']) && $time >= $this->options['slow'],
//                'file' => $caller['file'],
//                'line' => $caller['line'],
//                'hash' => $this->familyHash($event),
//            ];
//        }
    }
}
