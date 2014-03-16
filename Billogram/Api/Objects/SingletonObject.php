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

namespace Billogram\Api\Objects;

use Billogram\Api\Exceptions\UnknownFieldError;

/**
 * Represents a remote singleton object on Billogram
 *
 * Implements __get for object-like access to the data of the remote object,
 * or use the 'data' property to access the backing array. The data in this
 * object and all sub-objects should be treated as read-only, the only way to
 * change the remote object is through the 'update' method.
 *
 * The represented object is initially "lazy" and will only be fetched on the
 * first access. If the remote data are changed, the local copy can be updated by
 * the 'refresh' method.
 *
 * See the online documentation for the actual structure of remote objects.
 *
 * @property \Billogram\Api $api
 * @property \Billogram\Api\Models\SimpleClass $objectClass
 */
class SingletonObject
{
    protected $objectClass = null;
    protected $urlName = null;
    protected $data = null;
    protected $api;

    /**
     * Constructor sets a url endpoint for the resource
     *
     * @param $api
     * @param $urlName
     */
    public function __construct($api, $urlName)
    {
        $this->api = $api;
        $this->urlName = $urlName;
    }

    /**
     * String representation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return "<Billogram object '" . $this->url() . "'" . ($this->data === null ? " (lazy)" : "") . ">";
    }

    /**
     * Returns the API url where you can receive this object.
     *
     * @return null|string
     */
    public function url()
    {
        if ($this->urlName)
            return $this->urlName;
        if ($this->objectClass)
            return $this->objectClass->url($this);
        return null;
    }

    /**
     * Makes a GET request and refreshes the local data with up-to-date info.
     *
     * @return $this
     */
    public function refresh()
    {
        $response = $this->api->get($this->url());
        $this->data = (array) $response->data;

        return $this;
    }

    /**
     * Updates the API object with $data.
     *
     * @param $data
     * @return $this
     */
    public function update($data)
    {
        $response = $this->api->put($this->url(), $data);
        $this->data = (array) $response->data;

        return $this;
    }

    /**
     * Wrapper method to easier access the specific parameters
     *
     * @param $key
     * @return null
     * @throws \Billogram\Api\Exceptions\UnknownFieldError
     */
    public function __get($key)
    {
        switch ($key) {
            case 'data':
                if (!$this->data)
                    $this->refresh();

                return $this->data;

            default:
                if (!$this->data)
                    $this->refresh();
                if (array_key_exists($key, $this->data))
                    return $this->data[$key];
                throw new UnknownFieldError("Invalid parameter: " . $key);
        }
    }
}
