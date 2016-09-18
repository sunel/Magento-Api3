<?php

namespace Sunel\Api\Provider;

use Sunel\Api\Config;
use Illuminate\Contracts\Container\Container;
use Sunel\Api\Http\Response;
use Sunel\Api\Auth\JWTAuth;
use Symfony\Component\Process\Exception\RuntimeException;

class Magento
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
        Response::setFormatters($this->prepareConfigValues($this->app['config']['response.formats']));
        //Response::setTransformer($this->app['api.transformer']);

        $this->registerRoutes();
    }

    public function register()
    {
        $this->setupConfig();
        $this->registerMiddleware();
        $this->registerAuth();
    }

    /**
     * Setup the configuration.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $config = new Config(\Mage::getConfig()->getNode('global/api3')->asArray());
        $this->app->instance('config', $config);
    }

    /**
     * Prepare an array of instantiable configuration instances.
     *
     * @param array $instances
     *
     * @return array
     */
    protected function prepareConfigValues(array $instances)
    {
        foreach ($instances as $key => $value) {
            $instances[$key] = $this->app->make($value);
        }

        return $instances;
    }

    /**
     * Register the middleware.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        $middlewares = $this->app['config']['middlewares'];
        if ($middlewares) {
            foreach ($middlewares as $key => $binding) {
                $this->app->bind($key, function ($app) use ($binding) {
                    if (strpos($binding, '/')!==false) {
                        $models = $app['config']['prefixs'];
                        $bindings = explode('/',$binding);
                        $baseClass = $models[$bindings[0]];
                        $binding = "{$baseClass}_".uc_words($bindings[1]);
                    }
                    return $app->make($binding);
                });
            }
        }
    }

    /**
     * Register the auth providers.
     *
     * @return void
     */
    protected function registerAuth() 
    {
        $this->app->singleton('Sunel\Api\Auth\JWTAuth', function ($app) {
            return new JWTAuth(\Mage::helper('api3/auth'));
        });
    }

    /**
     * Fetch all routes of the given api type from config files and
     * register it.
     */
    public function registerRoutes()
    {
        $resources = $this->app['config']['routes'];
        foreach ($resources as $resourceKey => $resource) {

            $class = "{$resource}_Api_Route@getRoutes";

            $this->app->call($class,[$this->app['api.router']]);
        }
        return $this;
    }
}
