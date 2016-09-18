<?php

namespace Sunel\Api\Http\Middleware;

use Closure;
use Sunel\Api\Router;
use Sunel\Api\Auth\Auth as Authentication;

class Auth
{
    /**
     * Router instance.
     *
     * @var \Sunel\Api\Router
     */
    protected $router;
    /**
     * Authenticator instance.
     *
     * @var \Sunel\Api\Auth\Auth
     */
    protected $auth;
    /**
     * Create a new auth middleware instance.
     *
     * @param \Sunel\Api\Router $router
     * @param \Sunel\Api\Auth\Auth      $auth
     */
    public function __construct(Router $router, Authentication $auth)
    {
        $this->router = $router;
        $this->auth = $auth;
    }
    /**
     * Perform authentication before a request is executed.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $provider = [])
    {
        $route = $this->router->getCurrentRoute();

        if(!is_array($provider)) {
            $provider = explode(',', $provider);
        }

        if (! $this->auth->check(false)) {
            $this->auth->authenticate($provider);
        }
        return $next($request);
    }
}