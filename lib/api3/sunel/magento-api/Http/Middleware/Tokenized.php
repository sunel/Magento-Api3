<?php

namespace Sunel\Api\Http\Middleware;

use Closure;
use Sunel\Api\Http\Parser\Token;
use Sunel\Api\Exception\TokenInvalidException;

class Tokenized
{
	protected $tokenParse;

	public function __construct(Token $tokenParse)
	{
		$this->tokenParse = $tokenParse;
	}
	/**
     * Perform authentication before a request is executed.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!$this->tokenParse->valid($request)){
        	throw new TokenInvalidException('Token not given');
        }
        return $next($request);
    }

}