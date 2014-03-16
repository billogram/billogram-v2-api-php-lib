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

namespace Billogram\Api\Models;

use Billogram\Api\Query;

/**
 * Represents a collection of remote objects on the Billogram service.
 *
 * Provides methods to search, fetch and create instances of the object type.
 *
 * See the online documentation for the actual structure of remote objects.
 *
 * @property \Billogram\Api $api
 */
class SimpleClass
{
    public $objectClass = 'Billogram\Api\Objects\SimpleObject';
    protected $api;
    protected $urlName;
    protected $objectIdField;

    /**
     * Constructor sets the base url and significant id field for the resource.
     *
     */
    public function __construct($api, $urlName, $objectIdField)
    {
        $this->api = $api;
        $this->urlName = $urlName;
        $this->objectIdField = $objectIdField;
    }

    /**
     * Create a query for objects of this type.
     *
     * @return \Billogram\Api\Query
     */
    public function query()
    {
        return new Query($this->api, $this);
    }

    /**
     * Finds an object by id $objectId and returns an object.
     *
     * @param $objectId
     * @return \Billogram\Api\Objects\SimpleObject
     */
    public function get($objectId)
    {
        $response = $this->api->get($this->url($objectId));
        $className = $this->objectClass;

        return new $className($this->api, $this, $response->data);
    }

    /**
     * Makes a POST request to the API and creates a new object.
     *
     * @param $data
     * @return \Billogram\Api\Objects\SimpleObject
     */
    public function create($data)
    {
        $response = $this->api->post($this->url(), $data);
        $className = $this->objectClass;

        return new $className($this->api, $this, $response->data);
    }

    /**
     * Formats and returns a URL to an object or object id.
     *
     * @param null $object
     * @return string
     */
    public function url($object = null)
    {
        if (is_object($object)) {
            $objectIdField = $this->objectIdField;

            return $this->urlName . '/' . $object->$objectIdField;
        } elseif ($object) {
            return $this->urlName . '/' . $object;
        }

        return $this->urlName;
    }
}
