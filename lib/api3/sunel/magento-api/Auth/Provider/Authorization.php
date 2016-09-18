<?php

namespace Sunel\Api\Auth\Provider;

use Illuminate\Http\Request;
use League\Route\Http\Exception\BadRequestException;

abstract class Authorization implements \Sunel\Api\Contracts\Auth\Provider
{
    /**
     * Array of provider specific options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Validate the requests authorization header for the provider.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \League\Route\Http\Exception\BadRequestException
     *
     * @return bool
     */
    public function validateAuthorizationHeader(Request $request)
    {
        if (static::startsWith(strtolower($request->headers->get('authorization')), $this->getAuthorizationMethod())) {
            return true;
        }

        throw new BadRequestException;
    }

    /**
     * Get the providers authorization method.
     *
     * @return string
     */
    abstract public function getAuthorizationMethod();

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }
}
