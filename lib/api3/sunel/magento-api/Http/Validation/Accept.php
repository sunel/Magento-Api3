<?php

namespace Sunel\Api\Http\Validation;

use Sunel\Api\Http\Request;
use Sunel\Api\Contracts\Http\Validator;
use League\Route\Http\Exception\BadRequestException;

class Accept implements Validator
{
    /**
     * Accept parser instance.
     *
     */
    protected $accept;
    /**
     * Indicates if the accept matching is strict.
     *
     * @var bool
     */
    protected $strict;
    /**
     * Create a new accept validator instance.
     *
     * @param        $accept
     * @param bool   $strict
     *
     * @return void
     */
    public function __construct($accept, $strict = false)
    {
        $this->accept = $accept;
        $this->strict = $strict;
    }
    
    public function validate(Request $request)
    {
        try {
            $this->accept->parse($request, $this->strict);
        } catch (BadRequestException $exception) {
            if ($request->getMethod() === 'OPTIONS') {
                return true;
            }
            throw $exception;
        }
    }
}
