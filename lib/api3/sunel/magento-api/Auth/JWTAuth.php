<?php

namespace Sunel\Api\Auth;

use OutOfBoundsException;
use Sunel\Api\Exception\TokenInvalidException;

class JWTAuth
{
    protected $manager;

    /**
     * @var \Tymon\JWTAuth\Token
     */
    protected $token;

    /**
     * @param  $manager
     *
     * @return void
     */
    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    /**
     * Authenticate a user via a token.
     *
     * @return \Tymon\JWTAuth\Contracts\JWTSubject|false
     */
    public function authenticate()
    {  
        try{
            $id = $this->getPayload()->getClaim('uid');
        } catch (OutOfBoundsException $e) {
            return false;
        }
        if (!$user = $this->manager->byId($id)) {
            return false;
        }
        return $user;
    }
    /**
     * Alias for authenticate().
     *
     * @return \Tymon\JWTAuth\Contracts\JWTSubject|false
     */
    public function toUser()
    {
        return $this->authenticate();
    }

    /**
     * Set the token.
     *
     * @param  \Tymon\JWTAuth\Token|string  $token
     *
     * @return $this
     */
    public function setToken($token)
    {
    	$this->validateStructure($token);
        $this->token = $token;
        return $this;
    }

    /**
     * Get the raw Payload instance.
     *
     * @return \Tymon\JWTAuth\Payload
     */
    public function getPayload()
    {
        return $this->manager->decode($this->token);
    }


    /**
     * @param  string  $token
     *
     * @throws \Sunel\Api\Exception\TokenInvalidException
     *
     * @return bool
     */
    protected function validateStructure($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new TokenInvalidException('Wrong number of segments');
        }
        $parts = array_filter(array_map('trim', $parts));
        if (count($parts) !== 3 || implode('.', $parts) !== $token) {
            throw new TokenInvalidException('Malformed token');
        }
        return true;
    }
}