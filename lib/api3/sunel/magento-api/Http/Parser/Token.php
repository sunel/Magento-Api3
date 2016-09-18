<?php

namespace Sunel\Api\Http\Parser;

use Sunel\Api\Http\Request;

class Token
{
	/**
     * The header name.
     *
     * @var string
     */
    protected $header = 'authorization';
    /**
     * The header prefix.
     *
     * @var string
     */
    protected $prefix = 'bearer';

    /**
     * The query string key.
     *
     * @var string
     */
    protected $key = 'token';


    /**
     * Attempt to parse the token from some other possible headers.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return null|string
     */
    protected function fromAltHeaders(Request $request)
    {
        return $request->server->get('HTTP_AUTHORIZATION') ?: $request->server->get('REDIRECT_HTTP_AUTHORIZATION');
    }

    /**
     * Attempt to parse the token from some other possible headers.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return null|string
     */
    protected function fromHeaders(Request $request)
    {
        $header = $request->headers->get($this->header) ?: $this->fromAltHeaders($request);
        if ($header && stripos($header, $this->prefix) === 0) {
            return trim(str_ireplace($this->prefix, '', $header));
        }
    }

    /**
     * Attempt to parse the token from query
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return null|string
     */
    protected function fromQuery(Request $request)
    {
    	 return $request->query($this->key);
    }

    /**
     * Try to parse the token from the request header.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return null|string
     */
    public function parse(Request $request)
    {
     	if(!$token = $this->fromHeaders($request)){
     		$token = $this->fromQuery($request);
     	}
     	return $token;   
    }

    public function valid(Request $request)
    {
    	if($this->parse($request)) {
    		return true;
    	}
    	return false;
    }
}