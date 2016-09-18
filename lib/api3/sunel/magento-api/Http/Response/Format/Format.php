<?php

namespace Sunel\Api\Http\Response\Format;

abstract class Format
{
    /**
     * Api request instance.
     *
     * @var \Sunel\Api\Http\Request
     */
    protected $request;

    /**
     * Api response instance.
     *
     * @var \Sunel\Api\Http\Response
     */
    protected $response;

    /**
     * Set the request instance.
     *
     * @param \Sunel\Api\Http\Request $request
     *
     * @return \Sunel\Api\Http\Response\Format\Format
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the response instance.
     *
     * @param \Sunel\Api\Http\Response $response
     *
     * @return \Sunel\Api\Http\Response\Format\Format
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Format an Eloquent model.
     *
     * @param  $model
     *
     * @return string
     */
    abstract public function formatModel($model);

    /**
     * Format an Eloquent collection.
     *
     * @param  $collection
     *
     * @return string
     */
    abstract public function formatCollection($collection);

    /**
     * Format an array or instance implementing Arrayable.
     *
     * @param array $content
     *
     * @return string
     */
    abstract public function formatArray($content);

    /**
     * Get the response content type.
     *
     * @return string
     */
    abstract public function getContentType();
}
