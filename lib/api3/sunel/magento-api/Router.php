<?php

namespace Sunel\Api;

use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use League\Route\Http\Exception\NotAcceptableException;
use Sunel\Api\Contract\ExceptionHandler;
use Illuminate\Contracts\Container\Container as ContianerContract;
use Sunel\Api\Exception\ActionNotDefinedException;
use Sunel\Api\Exception\UnknownVersionException;
use Sunel\Api\Http\Parser\Accept as AcceptParser;
use Sunel\Api\Http\Request;
use Sunel\Api\Http\Response;
use Sunel\Api\Routing\Route;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Process\Exception\RuntimeException;

class Router
{
    /**
     * Accept parser instance.
     *
     * @var \Sunel\Api\Http\Parser\Accept
     */
    protected $accept;
    /**
     * Exception handler instance.
     *
     * @var \Sunel\Api\Contract\ExceptionHandler
     */
    protected $exception;

    /**
     * Application container instance.
     *
     * @var \Sunel\Api\Container
     */
    protected $container;
    /**
     * Group stack array.
     *
     * @var array
     */
    protected $groupStack = [];
    /**
     * Indicates if the request is conditional.
     *
     * @var bool
     */
    protected $conditionalRequest = true;
    /**
     * The current route being dispatched.
     *
     * @var \Dingo\Api\Routing\Route
     */
    protected $currentRoute;
    /**
     * The number of routes dispatched.
     *
     * @var int
     */
    protected $routesDispatched = 0;
    /**
     * The API domain.
     *
     * @var string
     */
    protected $domain;
	
	/**
     * The API prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Array of registered routes.
     *
     * @var array
     */
    protected $routes = [];

    protected $patternMatchers = [
        '/{(.+?):number}/'        => '{$1:[0-9]+}',
        '/{(.+?):word}/'          => '{$1:[a-zA-Z]+}',
        '/{(.+?):alphanum_dash}/' => '{$1:[a-zA-Z0-9-_]+}'
    ];

    public function __construct(AcceptParser $accept, ExceptionHandler $exception, ContianerContract $container, $domain, $prefix)
    {
        $this->accept = $accept;
        $this->exception = $exception;
        $this->container = $container;
        $this->domain = $domain;
		$this->prefix = $prefix;
    }

    /**
     * An alias for calling the group method, allows a more fluent API
     * for registering a new API version group with optional
     * attributes and a required callback.
     *
     * This method can be called without the third parameter, however,
     * the callback should always be the last paramter.
     *
     * @param string         $version
     * @param array|callable $second
     * @param callable       $third
     *
     * @return void
     */
    public function version($version, $second, $third = null)
    {
        if (func_num_args() == 2) {
            list($version, $callback, $attributes) = array_merge(func_get_args(), [[]]);
        } else {
            list($version, $attributes, $callback) = func_get_args();
        }
        $attributes = array_merge($attributes, ['version' => $version]);
        $this->group($attributes, $callback);
    }

    /**
     * Create a new route group.
     *
     * @param array    $attributes
     * @param callable $callback
     *
     * @return void
     */
    public function group(array $attributes, $callback)
    {
        if (! isset($attributes['conditionalRequest'])) {
            $attributes['conditionalRequest'] = $this->conditionalRequest;
        }
        $attributes = $this->mergeLastGroupAttributes($attributes);
        if (! isset($attributes['version'])) {
            throw new RuntimeException('A version is required for an API group definition.');
        } else {
            $attributes['version'] = (array) $attributes['version'];
        }
        if ((! isset($attributes['domain']) || empty($attributes['domain'])) && isset($this->domain)) {
            $attributes['domain'] = $this->domain;
        }
		if ((! isset($attributes['prefix']) || empty($attributes['prefix'])) && isset($this->prefix)) {
            $attributes['prefix'] = $this->prefix;
        }
        $this->groupStack[] = $attributes;
        call_user_func($callback, $this);
        array_pop($this->groupStack);
    }

    /**
     * Create a new GET route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function get($uri, $action)
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Create a new POST route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Create a new PUT route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Create a new PATCH route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Create a new DELETE route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Create a new OPTIONS route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function options($uri, $action)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Create a new route that responding to all verbs.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function any($uri, $action)
    {
        $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];

        return $this->addRoute($verbs, $uri, $action);
    }

    /**
     * Create a new route with the given verbs.
     *
     * @param array|string          $methods
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function match($methods, $uri, $action)
    {
        return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
    }

    /**
     * Add a route to the routing adapter.
     *
     * @param string|array          $methods
     * @param string                $uri
     * @param string|array|callable $action
     *
     * @return mixed
     */
    public function addRoute($methods, $uri, $action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        } elseif (empty($action['uses'])) {
            throw new RuntimeException('A action is required for an API route definition.');
        }

        $action = $this->mergeLastGroupAttributes($action);
        $uri = $uri === '/' ? $uri : '/'.trim($uri, '/');
        if (! empty($action['prefix'])) {
            $uri = '/'.ltrim(rtrim(trim($action['prefix'], '/').'/'.trim($uri, '/'), '/'), '/');
            unset($action['prefix']);
        }
        $action['uri'] = $uri;

        $this->createRouteCollections($action['version']);

        foreach ($action['version'] as $version) {
            foreach ($this->breakUriSegments($uri) as $uri) {
                $uri = $this->parseRouteString($uri);
                $this->routes[$version]->addRoute((array) $methods, $uri, $action);
            }
        }
    }

    /**
     * Create the route collections for the versions.
     *
     * @param array $versions
     *
     * @return void
     */
    protected function createRouteCollections(array $versions)
    {
        foreach ($versions as $version) {
            if (! isset($this->routes[$version])) {
                $this->routes[$version] = new RouteCollector(new StdRouteParser, new GcbDataGenerator);
            }
        }
    }

    /**
     * Get all routes or only for a specific version.
     *
     * @param string $version
     *
     * @return mixed
     */
    public function getRoutes($version = null)
    {
        if (! is_null($version)) {
            return $this->routes[$version];
        }
        return $this->routes;
    }

    /**
     * Get routes in an iterable form.
     *
     * @param string $version
     *
     * @return \ArrayIterator
     */
    public function getIterableRoutes($version = null)
    {
        $iterable = [];
        foreach ($this->getRoutes($version) as $version => $collector) {
            $routeData = $collector->getData();
            // The first element in the array are the static routes that do not have any parameters.
            foreach ($routeData[0] as $uri => $route) {
                $iterable[$version][] = array_shift($route);
            }
            // The second element is the more complicated regex routes that have parameters.
            foreach ($routeData[1] as $method => $routes) {
                if ($method === 'HEAD') {
                    continue;
                }
                foreach ($routes as $data) {
                    foreach ($data['routeMap'] as list($route, $parameters)) {
                        $iterable[$version][] = $route;
                    }
                }
            }
        }
        return new \ArrayIterator($iterable);
    }

    /**
     * Merge the last groups attributes.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function mergeLastGroupAttributes(array $attributes)
    {
        if (empty($this->groupStack)) {
            return $this->mergeGroup($attributes, []);
        }
        return $this->mergeGroup($attributes, end($this->groupStack));
    }
    /**
     * Merge the given group attributes.
     *
     * @param array $new
     * @param array $old
     *
     * @return array
     */
    protected function mergeGroup(array $new, array $old)
    {
    	$new['prefix'] = $this->formatPrefix($new, $old);
		
        foreach (['middleware', 'providers', 'scopes', 'before', 'after'] as $option) {
            $new[$option] = $this->formatArrayBasedOption($option, $new);
        }
        if (isset($new['conditionalRequest'])) {
            unset($old['conditionalRequest']);
        }
        $new['where'] = array_merge(array_get($old, 'where', []), array_get($new, 'where', []));
        
        return array_merge_recursive(array_except($old, ['namespace', 'prefix', 'where', 'as']), $new);
    }

    /**
     * Dispatch a request.
     *
     * @param \Sunel\Api\Http\Request $request
     *
     * @throws \Exception
     *
     * @return \Sunel\Api\Http\Response
     */
    public function dispatch(Request $request)
    {
        $this->currentRoute = null;
        $accept = $this->accept->parse($request);
        $this->container->instance('request', $request);
        $this->routesDispatched++;
        try {
            if (! isset($this->routes[$accept['version']])) {
                throw new UnknownVersionException;
            }
            $routes = $this->routes[$accept['version']];
            $this->container->setDispatcher(
                new GroupCountBased($routes->getData())
            );
            $this->normalizeRequestUri($request);
            $response = $this->container->dispatch($request);
        } catch (\Exception $exception) {
            $response = $this->exception->handle($exception);
        }
        return $this->prepareResponse($response, $request, $accept['format']);
    }

    /**
     * Prepare a response by transforming and formatting it correctly.
     *
     * @param mixed                   $response
     * @param \Sunel\Api\Http\Request $request
     * @param string                  $format
     * @param bool                    $raw
     *
     * @return \Sunel\Api\Http\Response
     */
    protected function prepareResponse($response, Request $request, $format)
    {
        if ($response instanceof Response) {
            // If we try and get a formatter that does not exist we'll let the exception
            // handler deal with it. At worst we'll get a generic JSON response that
            // a consumer can hopefully deal with. Ideally they won't be using
            // an unsupported format.
            try {
                $response->getFormatter($format)->setResponse($response)->setRequest($request);
            } catch (NotAcceptableException $exception) {
                return $this->exception->handle($exception);
            }

            $response = $response->morph($format);
        }

        if ($response->isSuccessful() && $this->requestIsConditional()) {
            if (! $response->headers->has('ETag')) {
                $response->setEtag(md5($response->getContent()));
            }

            $response->isNotModified($request);
        }

        return $response;
    }

    /**
     * Get the current request instance.
     *
     * @return \Dingo\Api\Http\Request
     */
    public function getCurrentRequest()
    {
        return $this->container['request'];
    }

    /**
     * Determine if the request is conditional.
     *
     * @return bool
     */
    protected function requestIsConditional()
    {
        return $this->getCurrentRoute()->requestIsConditional();
    }

    /**
     * Set the conditional request.
     *
     * @param bool $conditionalRequest
     *
     * @return void
     */
    public function setConditionalRequest($conditionalRequest)
    {
        $this->conditionalRequest = $conditionalRequest;
    }

    /**
     * Get the current route instance.
     *
     * @return \Sunel\Api\Routing\Route
     */
    public function getCurrentRoute()
    {
        if (isset($this->currentRoute)) {
            return $this->currentRoute;
        } elseif (! $this->hasDispatchedRoutes()) {
            return;
        }

        $request = $this->container['request'];

        return $this->currentRoute = $this->createRoute($request->route());
    }

    /**
     * Determine if the router has dispatched any routes.
     *
     * @return bool
     */
    public function hasDispatchedRoutes()
    {
        return $this->routesDispatched > 0;
    }

    /**
     * Create a new route instance from an adapter route.
     *
     * @param array $route
     *
     * @return \Sunel\Api\Routing\Route
     */
    public function createRoute($route)
    {
        return new Route($this->container, $this->container['request'], $route);
    }
	
	/**
     * Format the prefix for the new group attributes.
     *
     * @param array $new
     * @param array $old
     *
     * @return string
     */
    protected function formatPrefix($new, $old)
    {
        if (isset($new['prefix'])) {
            return trim(array_get($old, 'prefix'), '/').'/'.trim($new['prefix'], '/');
        }
        return array_get($old, 'prefix', '');
    }

    /**
     * Format an array based option in a route action.
     *
     * @param string $option
     * @param array  $new
     *
     * @return array
     */
    protected function formatArrayBasedOption($option, array $new)
    {
        $value = array_get($new, $option, []);
        return is_string($value) ? explode('|', $value) : $value;
    }

    /**
     * Break a URI that has optional segments into individual URIs.
     *
     * @param string $uri
     *
     * @return array
     */
    protected function breakUriSegments($uri)
    {
        if (! str_contains($uri, '?}')) {
            return (array) $uri;
        }
        $segments = preg_split(
            '/\/(\{.*?\})/',
            preg_replace('/\{(.*?)\?\}/', '{$1}', $uri),
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        $uris = [];
        while ($segments) {
            $uris[] = implode('/', $segments);
            array_pop($segments);
        }
        return $uris;
    }

    /**
     * Normalize the request URI so that it can properly dispatch it.
     *
     * @param \Sunel\Api\Http\Request $request
     *
     * @return void
     */
    protected function normalizeRequestUri(Request $request)
    {
        $query = $request->server->get('QUERY_STRING');
        $uri = '/'.trim(str_replace('?'.$query, '', $request->server->get('REQUEST_URI')), '/').'?'.$query;
        $request->server->set('REQUEST_URI', $uri);
    }

    /**
     * Add a convenient pattern matcher to the internal array for use with all routes.
     *
     * @param string $keyWord
     * @param string $regex
     */
    public function addPatternMatcher($keyWord, $regex)
    {
        // Since the user is passing in a human-readable word, we convert that to the appropriate regex
        $pattern = '/{(.+?):' . $keyWord . '}/';
        $regex = '{$1:' . $regex . '}';
        $this->patternMatchers[$pattern] = $regex;
    }
    /**
     * Convenience method to convert pre-defined key words in to regex strings
     *
     * @param  string $route
     * @return string
     */
    protected function parseRouteString($route)
    {
        return preg_replace(array_keys($this->patternMatchers), array_values($this->patternMatchers), $route);
    }
}
