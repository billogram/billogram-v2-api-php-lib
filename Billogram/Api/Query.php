<?php

/**
 * Copyright (c) 2013 Billogram AB
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package Billogram_Api
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @author Billogram AB
 */

namespace Billogram\Api;

/**
 * Builds queries and fetches pages of remote objects
 *
 * Due to internal limitations in Billogram it is currently only possible to
 * filter on a single field or special query at a time. This may change in the
 * future. When it does the API will continue supporting the old filtering
 * mechanism, however this client library will be updated to use the new one,
 * and at that point we will strongly recommend all applications be updated.
 *
 * The exact fields and special queries available for each object type varies,
 * see the online documentation for details.
 *
 * @property \Billogram\Api $api
 * @property \Billogram\Api\Models\SimpleClass $typeClass
 */
class Query
{
    private $typeClass;
    private $filter = array();
    private $countCached = null;
    private $pageSize = 100;
    private $order = array();
    private $api;

    /**
     * Initiated with the Billogram API object and the parent model as
     * $typeClass.
     *
     * @param $api
     * @param $typeClass
     */
    public function __construct($api, $typeClass)
    {
        $this->api = $api;
        $this->typeClass = $typeClass;
    }

    /**
     * Makes a GET request to the API with parameters for page size,
     * page number, filtering and order values. Returns the API response.
     *
     * @param int $pageNumber
     * @return mixed
     */
    private function makeQuery($pageNumber = 1)
    {
        $params = array(
            'page_size' => $this->pageSize,
            'page' => $pageNumber
        );
        $params = array_merge($params, $this->filter);
        $params = array_merge($params, $this->order);
        $response = $this->api->get($this->typeClass->url(), $params);
        $this->countCached = $response->meta->total_count;

        return $response;
    }

    /**
     * Sets which field to order on. $orderDirection can be 'asc' or 'desc'.
     *
     * @param $orderField
     * @param $orderDirection
     * @return $this
     */
    public function order($orderField, $orderDirection)
    {
        $this->order = array(
            'order_field' => $orderField,
            'order_direction' => $orderDirection
        );

        return $this;
    }

    /**
     * Sets the page size.
     *
     * @param $pageSize
     * @return $this
     */
    public function pageSize($pageSize)
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * Total amount of objects matched by the current query, reading this
     * may cause a remote request.
     *
     * @return null
     */
    public function count()
    {
        if ($this->countCached === null) {
            $pageSize = $this->pageSize;
            $this->pageSize = 1;
            $this->makeQuery(1);
            $this->pageSize = $pageSize;
        }

        return $this->countCached;
    }

    /**
     * Total number of pages required for all objects based on current pagesize,
     * reading this may cause a remote request.
     *
     * @return float
     */
    public function totalPages()
    {
        return ceil($this->count() / $this->pageSize);
    }

    /**
     * Sets up filtering rules for the query.
     *
     * @param null $filterType
     * @param null $filterField
     * @param null $filterValue
     * @return $this
     */
    public function makeFilter(
        $filterType = null,
        $filterField = null,
        $filterValue = null
    ) {
        if ($filterType === null &&
            $filterField === null &&
            $filterValue === null)
            $this->filter = array();
        else
            $this->filter = array(
                'filter_type' => $filterType,
                'filter_field' => $filterField,
                'filter_value' => $filterValue
            );

        return $this;
    }

    /**
     * Removes any previous filtering rules.
     *
     * @return $this
     */
    public function removeFilter()
    {
        $this->filter = array();

        return $this;
    }

    /**
     * Filter by a specific field and an exact value.
     *
     * @param $filterField
     * @param $filterValue
     * @return $this
     */
    public function filterField($filterField, $filterValue)
    {
        return $this->makeFilter('field', $filterField, $filterValue);
    }

    /**
     * Filter by a specific field and looks for prefix matches.
     *
     * @param $filterField
     * @param $filterValue
     * @return $this
     */
    public function filterPrefix($filterField, $filterValue)
    {
        return $this->makeFilter('field-prefix', $filterField, $filterValue);
    }

    /**
     * Filter by a specific field and looks for substring matches.
     *
     * @param $filterField
     * @param $filterValue
     * @return $this
     */
    public function filterSearch($filterField, $filterValue)
    {
        return $this->makeFilter('field-search', $filterField, $filterValue);
    }

    /**
     * Filter on a special query.
     *
     * @param $filterField
     * @param $filterValue
     * @return $this
     */
    public function filterSpecial($filterField, $filterValue)
    {
        return $this->makeFilter('special', $filterField, $filterValue);
    }

    /**
     * Filter by a full data search (exact meaning depends on object type).
     *
     * @param $searchTerms
     * @return $this
     */
    public function search($searchTerms)
    {
        return $this->makeFilter('special', 'search', $searchTerms);
    }

    /**
     * Fetch objects for the one-based page number.
     *
     * @param $pageNumber
     * @return array
     */
    public function getPage($pageNumber)
    {
        $response = $this->makeQuery($pageNumber);
        $className = $this->typeClass->objectClass;
        $objects = array();
        if (!isset($response->data) || !$response->data)
            return array();
        foreach ($response->data as $object) {
            $objects[] = new $className($this->api, $this->typeClass, $object);
        }

        return $objects;
    }
}
