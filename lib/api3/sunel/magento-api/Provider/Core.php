<?php

namespace Sunel\Api\Provider;

use Illuminate\Contracts\Container\Container;
use Sunel\Api\Exception\ExceptionHandler;
use Sunel\Api\Http\Parser\Accept as AcceptParser;
use Sunel\Api\Http\Parser\Token as TokenParser;
use Sunel\Api\Http\RequestHandler;
use Sunel\Api\Http\RequestValidator;
use Sunel\Api\Http\Validation\Accept;
use Sunel\Api\Http\Middleware;
use Sunel\Api\Http\Response;
use Sunel\Api\Http\Request;
use Sunel\Api\Auth\Auth;
use Sunel\Api\Router;

class Core
{
    /**
     * Application container instance.
     *
     * @var \Sunel\Api\Container
     */
    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function boot()
    {
        Request::setAcceptParser($this->app['Sunel\Api\Http\Parser\Accept']);
    }

    public function register()
    {
        $this->registerHttpParsers();
        $this->registerRouter();
        $this->registerExceptionHandler();
        $this->registerAuth();
        $this->registerHttpValidation();
        $this->registerMiddleware();
    }

    /**
     * Register the exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        $this->app->alias('api.exception', 'Sunel\Api\Contracts\ExceptionHandler');
        $this->app->singleton('api.exception', function ($app) {
            $config = $app['config'];
            return new ExceptionHandler([
                'error' => [
                    'message' => ':message',
                    'errors' => ':errors',
                    'code' => ':code',
                    'status_code' => ':status_code',
                    'debug' => ':debug'
                ]
            ], (bool) $config['debug']);
        });
    }

     /**
     * Register the auth.
     *
     * @return void
     */
    protected function registerAuth()
    {
        $this->app->singleton('api.auth', function ($app) {
            $config = $app['config'];
            return new Auth($app['api.router'], $app, $config['auth_adapters']);
        });
    }

    /**
     * Register the router.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('api.router', function ($app) {
            $config = $app['config'];
            $router = new Router(
                $app['Sunel\Api\Http\Parser\Accept'],
                $app['api.exception'],
                $app,
                $config['domain'],
                $config['prefix']
            );
            $router->setConditionalRequest($config['conditionalRequest']);
            return $router;
        });
    }

    /**
     * Register the HTTP validation.
     *
     * @return void
     */
    protected function registerHttpValidation()
    {
        $this->app->singleton('api.http.validator', function ($app) {
            return new RequestValidator($app);
        });

        $this->app->singleton('Sunel\Api\Http\Validation\Accept', function ($app) {
            $config = $app['config'];
            return new Accept(
                $this->app['Sunel\Api\Http\Parser\Accept'],
                (bool) $config['strict']
            );
        });
    }

    /**
     * Register the HTTP parsers.
     *
     * @return void
     */
    protected function registerHttpParsers()
    {
        $this->app->singleton('Sunel\Api\Http\Parser\Accept', function ($app) {
            $config = $app['config'];
            return new AcceptParser(
                $config['standardsTree'], 
                $config['subtype'], 
                $config['version'], 
                $config['defaultFormat']
            );
        });
    }

    /**
     * Register the middleware.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        $this->app->singleton('api.response', function ($app) {
            return new Response;
        });
        $this->app->singleton('Api\RequestHandler', function ($app) {
            return new RequestHandler($app, $app['api.exception'], $app['api.router'], $app['api.http.validator']);
        });
        $this->app->singleton('Sunel\Api\Http\Middleware\Auth', function ($app) {
            return new Middleware\Auth($app['api.router'], $app['api.auth']);
        });
        $this->app->singleton('Sunel\Api\Http\Middleware\Tokenized', function ($app) {
            return new Middleware\Tokenized(new TokenParser);
        });
    }
}
