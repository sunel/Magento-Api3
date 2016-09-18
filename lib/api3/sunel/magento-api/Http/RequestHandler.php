<?php

namespace Sunel\Api\Http;

use Sunel\Api\Router;
use Sunel\Api\Application;
use Sunel\Api\Http\RequestValidator;
use Sunel\Api\Contract\ExceptionHandler;

class RequestHandler
{
    /**
     * Application instance.
     * 
     */
    protected $app;

    /**
     * Exception handler instance.
     *
     */
    protected $exception;

    /**
     * Router instance.
     *
     */
    protected $router;

    /**
     * HTTP validator instance.
     *
     */
    protected $validator;

    /**
     * Create a new request middleware instance.
     *
     * @param \Sunel\Api\Application $app
     * @param \Sunel\Api\Contract\ExceptionHandler         $exception
     * @param \Sunel\Api\Router                            $router
     * @param \Sunel\Api\Http\RequestValidator             $validator
     *
     * @return void
     */
    public function __construct(Application $app, ExceptionHandler $exception, Router $router, RequestValidator $validator)
    {
        $this->app = $app;
        $this->exception = $exception;
        $this->router = $router;
        $this->validator = $validator;
    }

    public function handle($request)
    {
        try {
            if ($this->validator->validateRequest($request)) {
                return $this->router->dispatch($request);
            }
        } catch (\Exception $exception) {
            return $this->exception->handle($exception);
        }
    }
}
