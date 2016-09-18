<?php
namespace Sunel\Api\Http;

use Sunel\Api\Contracts\Container;
use Sunel\Api\Contracts\Http\Validator;

class RequestValidator
{
    /**
     * Container instance.
     *
     * @var \Sunel\Api\Container
     */
    protected $container;

    /**
     * Array of request validators.
     *
     * @var array
     */
    protected $validators = [
    ];

    /**
     * Create a new request validator instance.
     *
     * @param \Sunel\Api\Container $container
     *
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Validate a request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request $request
     *
     * @return bool
     */
    public function validateRequest($request)
    {
        #TODO Need to Add more validation
        $passed = true;

        foreach ($this->validators as $validator) {
            $validator = $this->container->make($validator);

            if ($validator instanceof Validator && $validator->validate($request)) {
                $passed = true;
            }
        }

        // The accept validator will always be run once any of the previous validators have
        // been run. This ensures that we only run the accept validator once we know we
        // have a request that is targetting the API.
        if ($passed) {
            $this->container->make('Sunel\Api\Http\Validation\Accept')->validate($request);
        }

        return $passed;
    }
}
