<?php

namespace Sunel\Api\Contracts;

interface ExceptionHandler
{
    /**
     * Handle an exception.
     *
     * @param \Exception $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(\Exception $exception);
}
