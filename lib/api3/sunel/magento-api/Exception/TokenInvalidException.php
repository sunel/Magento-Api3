<?php

namespace Sunel\Api\Exception;

use League\Route\Http\Exception as HttpException;

class TokenInvalidException extends HttpException
{
    /**
     * Create a new unknown version exception instance.
     *
     * @param string     $message
     * @param \Exception $previous
     * @param int        $code
     *
     * @return void
     */
    public function __construct($message = null, Exception $previous = null, $code = 0)
    {
        parent::__construct(400, $message, $previous, [], $code);
    }
}