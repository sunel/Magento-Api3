<?php

namespace Sunel\Api\Http;

use Illuminate\Http\Request as IlluminateRequest;
use Sunel\Api\Http\Parser\Accept;

class Request extends IlluminateRequest
{
    /**
     * Accept parser instance.
     *
     * @var \Dingo\Api\Http\Parser\Accept
     */
    protected static $acceptParser;

	protected $dispactedRoute = null;

	/**
     * Get the route handling the request.
     *
     * @param string|null $param
     *
     * @return object|string
     */
    public function route($param = null)
    {
    	if (is_null($param)) {
    		return $this->dispactedRoute;
    	}
        return $this->dispactedRoute = $param;
    }

    /**
     * Get the defined version.
     *
     * @return string
     */
    public function version()
    {
        $this->parseAcceptHeader();

        return $this->accept['version'];
    }

    /**
     * Get the defined subtype.
     *
     * @return string
     */
    public function subtype()
    {
        $this->parseAcceptHeader();

        return $this->accept['subtype'];
    }

    /**
     * Get the expected format type.
     *
     * @return string
     */
    public function format($default = 'html')
    {
        $this->parseAcceptHeader();

        return $this->accept['format'] ?: parent::format($default);
    }

    /**
     * Parse the accept header.
     *
     * @return void
     */
    protected function parseAcceptHeader()
    {
        if ($this->accept) {
            return;
        }

        $this->accept = static::$acceptParser->parse($this);
    }

    /**
     * Set the accept parser instance.
     *
     * @param \Sunel\Api\Http\Parser\Accept $acceptParser
     *
     * @return void
     */
    public static function setAcceptParser(Accept $acceptParser)
    {
        static::$acceptParser = $acceptParser;
    }

    /**
     * Get the accept parser instance.
     *
     * @return \Sunel\Api\Http\Parser\Accept
     */
    public static function getAcceptParser()
    {
        return static::$acceptParser;
    }
}
