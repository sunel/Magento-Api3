<?php

abstract class Sunel_Api_Model_Resource
{
    /**
     * The container implementation.
     */
    protected $container;

	/**
     * Request.
     */
    protected $request;


    /**
     * Create a new class instance.
     *
     * @param $container
     * @return void
     */
    public function __construct(\Illuminate\Contracts\Container\Container $container)
    {
        $this->container = $container;
    }

	/**
     * Return an error response.
     *
     * @param string $message
     * @param int    $statusCode
     *
     * @throws \League\Route\Http\Exception
     *
     * @return void
     */
    public function error($message, $statusCode)
    {
        throw new \League\Route\Http\Exception($statusCode, $message);
    }

    protected function success(array $data, $statusCode = 200)
    {
        $data = array_merge(array('status' => 'success', 'code' => $statusCode), (array) $data);

        return $this->_render($data)->setStatusCode($statusCode);
    }

    /**
     * Respond with a created response and associate a location if provided.
     *
     * @param null|string $location
     *
     * @return \Sunel\Api\Http\Response
     */
    public function created($location = null, $content = null)
    {
        $response = $this->response()->setContent($content);
        $response->setStatusCode(201);

        if (! is_null($location)) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Respond with an accepted response and associate a location and/or content if provided.
     *
     * @param null|string $location
     * @param mixed       $content
     *
     * @return \Sunel\Api\Http\Response
     */
    public function accepted($location = null, $content = null)
    {
        $response = $this->response()->setContent($content);
        $response->setStatusCode(202);

        if (! is_null($location)) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Respond with a no content response.
     *
     * @return \Sunel\Api\Http\Response
     */
    public function noContent()
    {
        $response = $this->response()->setContent(null);

        return $response->setStatusCode(204);
    }

    /**
     * Return a 404 not found error.
     *
     * @param string $message
     *
     * @throws \League\Route\Http\Exception
     *
     * @return void
     */
    public function errorNotFound($message = 'Not Found')
    {
        $this->error($message, 404);
    }

    /**
     * Return a 400 bad request error.
     *
     * @param string $message
     *
     * @throws \League\Route\Http\Exception
     *
     * @return void
     */
    public function errorBadRequest($message = 'Bad Request')
    {
        $this->error($message, 400);
    }

    /**
     * Return a 403 forbidden error.
     *
     * @param string $message
     *
     * @throws \League\Route\Http\Exception
     *
     * @return void
     */
    public function errorForbidden($message = 'Forbidden')
    {
        $this->error($message, 403);
    }

    /**
     * Return a 500 internal server error.
     *
     * @param string $message
     *
     * @throws \League\Route\Http\Exception
     *
     * @return void
     */
    public function errorInternal($message = 'Internal Error')
    {
        $this->error($message, 500);
    }

    /**
     * Return a 401 unauthorized error.
     *
     * @param string $message
     *
     * @throws \League\Route\Http\Exception
     *
     * @return void
     */
    public function errorUnauthorized($message = 'Unauthorized')
    {
        $this->error($message, 401);
    }

    /**
     * Return a 405 method not allowed error.
     *
     * @param string $message
     *
     * @throws \League\Route\Http\Exception
     *
     * @return void
     */
    public function errorMethodNotAllowed($message = 'Method Not Allowed')
    {
        $this->error($message, 405);
    }

     /**
     * Render data using registered Renderer.
     *
     * @param mixed $data
     */
    protected function _render($data)
    {
        return $this->response()
                ->setContent($data);
    }

     /**
     * Get response.
     *
     * @return \Sunel\Api\Http\Response
     */
    public function response()
    {
        return $this->container['api.response'];
    }

    /**
     * Get request.
     *
     * @throws Exception
     */
    public function getRequest()
    {
        if (!$this->request) {
            throw new Exception('Request is not set.');
        }

        return $this->request;
    }

    /**
     * Set request.
     *
     * @param $request
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

     /**
     * Get the token.
     *
     * @return mixed
     */
    protected function token()
    {
        $tokenParser = $this->container['Sunel\Api\Http\Parser\Token'];
        return $tokenParser->parse($this->getRequest());
    }    

    /**
     * Get the authenticated user.
     *
     * @return mixed
     */
    protected function user()
    {
        return $this->auth()->user();
    }

    /**
     * Get the auth instance.
     *
     * @return \Dingo\Api\Auth\Auth
     */
    protected function auth()
    {
        return $this->container['api.auth'];
    }

    /**
     * Translate a phrase.
     *
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        return Mage::app()->getTranslator()->translate($args);
    }

     /**
     * Magically handle calls to certain properties.
     *
     * @param string $key
     *
     * @throws \ErrorException
     *
     * @return mixed
     */
    public function __get($key)
    {
        $callable = [
            'token', 'user', 'auth', 'response',
        ];

        if (in_array($key, $callable) && method_exists($this, $key)) {
            return $this->$key();
        }

        throw new ErrorException('Undefined property '.get_class($this).'::'.$key);
    }

    /**
     * Magically handle calls to certain methods on the response factory.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \ErrorException
     *
     * @return \Dingo\Api\Http\Response
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->response(), $method) || $method == 'array') {
            return call_user_func_array([$this->response(), $method], $parameters);
        }

        throw new ErrorException('Undefined method '.get_class($this).'::'.$method);
    }
}