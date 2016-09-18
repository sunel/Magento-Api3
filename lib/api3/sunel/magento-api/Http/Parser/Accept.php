<?php

namespace Sunel\Api\Http\Parser;

use League\Route\Http\Exception\BadRequestException;

class Accept
{
    /**
     * Create a new accept parser instance.
     *
     * @param string $standardsTree
     * @param string $subtype
     * @param string $version
     * @param string $format
     *
     * @return void
     */
    public function __construct($standardsTree, $subtype, $version, $format)
    {
        $this->standardsTree = $standardsTree;
        $this->subtype = $subtype;
        $this->version = $version;
        $this->format = $format;
    }

    /**
     * Parse the accept header on the incoming request. If strict is enabled
     * then the accept header must be available and must be a valid match.
     *
     * @param        $request
     * @param bool   $strict
     *
     * @throws \League\Route\Http\Exception\BadRequestExceptio
     *
     * @return array
     */
    public function parse($request, $strict = false)
    {
        $default = 'application/'.$this->standardsTree.'.'.$this->subtype.'.'.$this->version.'+'.$this->format;
        $pattern = '/application\/'.$this->standardsTree.'\.('.$this->subtype.')\.(v?[\d\.]+)\+([\w]+)/';
        if (! preg_match($pattern, $request->header('accept'), $matches)) {
            if ($strict) {
                throw new BadRequestException('Accept header could not be properly parsed because of a strict matching process.');
            }
            preg_match($pattern, $default, $matches);
        }
        return array_combine(['subtype', 'version', 'format'], array_slice($matches, 1));
    }
}
