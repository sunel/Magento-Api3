<?php

namespace Sunel\Api\Contracts\Http;

use Sunel\Api\Http\Request;

interface Validator
{
    public function validate(Request $request);
}
