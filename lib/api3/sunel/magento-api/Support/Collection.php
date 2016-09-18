<?php

namespace Sunel\Api\Support;

class Collection extends \Varien_Data_Collection
{
    /**
     * Create a new collection.
     *
     * @param  mixed  $items
     * @return void
     */
    public function __construct($items = [])
    {
        $this->_items = $items;
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->_items);
    }

    public function first()
    {
        return $this->getFirstItem();
    }

    /**
     * Convert collection to array
     *
     * @return array
     */
    public function toArray($arrRequiredFields = array())
    {
        $arrItems = array();
        foreach ($this as $item) {
            $arrItems[] = $item->toArray($arrRequiredFields);
        }
        return $arrItems;
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Serialize the Collection Pagination.
     *
     * @return array
     */
    public function pagination()
    {
        if (!$this->getSize()) {
            return array();
        }

        $currentPage = (int) $this->getCurPage();
        $lastPage = (int) $this->getLastPageNumber();
        $pagination = array(
            'total' => (int) $this->getSize(),
            'count' => (int) $this->count(),
            'per_page' => (int) $this->getPageSize(),
            'current_page' => $currentPage,
            'total_pages' => $lastPage,
        );

        return array('pagination' => $pagination);
    }
}
