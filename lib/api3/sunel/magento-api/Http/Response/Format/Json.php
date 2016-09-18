<?php

namespace Sunel\Api\Http\Response\Format;

class Json extends Format
{
    /**
     * Format an model.
     *
     * @param  $model
     *
     * @return string
     */
    public function formatModel($model)
    {
        $key = array_pop(explode('/', $model->getResourceName()));

        if (strstr('_', $key) === false) {
            $key = camel_case($key);
        }
        
        return $this->encode([$key => $model->toArray()]);
    }

    /**
     * Format an collection.
     *
     * @param $collection
     *
     * @return string
     */
    public function formatCollection($collection)
    {
        if ($collection->isEmpty()) {
            return $this->encode([]);
        }

        $model = $collection->first();
        $key = array_pop(explode('/', $model->getResourceName()));

        if (strstr('_', $key) === false) {
            $key = camel_case($key);
        }

        return $this->encode([$key => $collection->toArray()]);
    }

    /**
     * Format an array or instance implementing Arrayable.
     *
     * @param array $content
     *
     * @return string
     */
    public function formatArray($content)
    {
        $content = $this->morphToArray($content);

        array_walk_recursive($content, function (&$value) {
            $value = $this->morphToArray($value);
        });

        return $this->encode($content);
    }

    /**
     * Get the response content type.
     *
     * @return string
     */
    public function getContentType()
    {
        return 'application/json';
    }

    /**
     * Morph a value to an array.
     *
     * @param array $value
     *
     * @return array
     */
    protected function morphToArray($value)
    {
        return $value instanceof \Varien_Object ? $value->toArray() : $value;
    }

    /**
     * Encode the content to its JSON representation.
     *
     * @param string $content
     *
     * @return string
     */
    protected function encode($content)
    {
        return json_encode($content);
    }
}
