<?php

namespace Sunel\Api;

use Error;
use Exception;
use FastRoute\Dispatcher;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Sunel\Api\Http\Response;
use Sunel\Api\Support\Collection;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class Application extends Container
{
    /**
     * The FastRoute dispatcher.
     *
     * @var \FastRoute\Dispatcher
     */
    protected $dispatcher;

    /**
     * List of servide Providers
     * 
     * @var array 
     */
    protected $providers = [];

    public function __construct()
    {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance('Illuminate\Contracts\Container\Container', $this);
    }

    public function run()
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
        $request = Http\Request::createFromGlobals();
        $response = $this['Api\RequestHandler']->handle($request);
        $response->send();
    }

    public function register($provider)
    {
        $class = $this->make($provider);
        $class->register();
        $this->providers[] = $class;
    }

    /**
     * Dispatch the incoming request.
     *
     * @param  SymfonyRequest  $request
     * @return Response
     */
    public function dispatch($request)
    {
        $method = $request->getMethod();
        $pathInfo = $request->getPathInfo();
        try {
            return $this->handleDispatcherResponse(
                $this->createDispatcher()->dispatch($method, $pathInfo)
            );
        } catch (Exception $e) {
            return $this->sendExceptionToHandler($e);
        } catch (Throwable $e) {
            return $this->sendExceptionToHandler($e);
        }
    }

    /**
     * Handle the response from the FastRoute dispatcher.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function handleDispatcherResponse($routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new NotFoundException;

            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedException($routeInfo[1]);

            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo);
        }
    }

    /**
     * Handle a route found by the dispatcher.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function handleFoundRoute($routeInfo)
    {
        $this['request']->route($routeInfo);
        
        $action = $routeInfo[1];

        // Pipe through route middleware...
        if (isset($action['middleware'])) {
            $middleware = $this->gatherMiddlewareClassNames($action['middleware']);

            return $this->prepareResponse($this->sendThroughPipeline($middleware, function () use ($routeInfo) {
                return $this->callActionOnArrayBasedRoute($routeInfo);
            }));
        }

        return $this->prepareResponse(
            $this->callActionOnArrayBasedRoute($routeInfo)
        );
    }

     /**
     * Call the Closure on the array based route.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callActionOnArrayBasedRoute($routeInfo)
    {
        $action = $routeInfo[1];

        if (isset($action['uses'])) {
            return $this->prepareResponse($this->callControllerAction($routeInfo));
        }
    }
    /**
     * Call a controller based route.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callControllerAction($routeInfo)
    {
        list($controller, $method) = explode('@', $routeInfo[1]['uses']);

        $models = $this['config']['prefixs'];
        $bindings = explode('/',$controller);
        $baseClass = $models[$bindings[0]];

        if (! method_exists($instance = $this->make("{$baseClass}_".uc_words($bindings[1])), $method)) {
            throw new NotFoundException("[$method] Method Not Found");
        }

        if (method_exists($instance , 'setRequest')) {
            $instance->setRequest($this['request']);
        }

        return $this->callControllerCallable(
            [$instance, $method], $routeInfo[2]
        );
    }

    /**
     * Call the callable for a controller action with the given parameters.
     *
     * @param  array  $callable
     * @param  array $parameters
     * @return mixed
     */
    protected function callControllerCallable(array $callable, array $parameters)
    {
        return $this->prepareResponse(
            $this->call($callable, $parameters)
        );
    }

    /**
     * Gather the full class names for the middleware short-cut string.
     *
     * @param  string  $middleware
     * @return array
     */
    protected function gatherMiddlewareClassNames($middlewares)
    {
        $computedMiddleware = [];
        foreach ((array) $middlewares as $middleware) {
            $middleware = is_string($middleware) ? explode('|', $middleware) : (array) $middleware;
            foreach ($middleware as $name) {
                list($name, $parameters) = array_pad(explode(':', $name, 2), 2, null);
                $computedMiddleware[$name] = ($name).($parameters ? ':'.$parameters : '');
            }
        }
        return array_values($computedMiddleware);
    }

    /**
     * Send the request through the pipeline with the given callback.
     *
     * @param  array  $middleware
     * @param  Closure  $then
     * @return mixed
     */
    protected function sendThroughPipeline(array $middleware, Closure $then)
    {
        if (count($middleware) > 0) {
            return (new Pipeline($this))
                ->send($this->make('request'))
                ->through($middleware)
                ->then($then);
        }
        return $then();
    }

    /**
     * Prepare the response for sending.
     *
     * @param  mixed  $response
     * @return Response
     */
    public function prepareResponse($response)
    {
        if(!method_exists($response, '__toString') && method_exists($response, 'getItems')) {
            $response = new Collection($response->getItems());
        }

        if (! $response instanceof SymfonyResponse) {
            $response = new Response($response);
        }
        return $response;
    }

    /**
     * Create a FastRoute dispatcher instance for the application.
     *
     * @return Dispatcher
     */
    protected function createDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Set the FastRoute dispatcher instance.
     *
     * @param  \FastRoute\Dispatcher  $dispatcher
     * @return void
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Send the exception to the handler and return the response.
     *
     * @param  \Throwable  $e
     * @return Response
     */
    protected function sendExceptionToHandler($e)
    {
        $handler = $this->app->make('Sunel\Api\Contracts\ExceptionHandler');

        if ($e instanceof Error) {
            $e = new FatalThrowableError($e);
        }

        #TODO Need use magento report
        //$handler->report($e);

        return $handler->render($this->make('request'), $e);
    }
}
