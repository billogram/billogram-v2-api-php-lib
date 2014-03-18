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
 **/

namespace Billogram\Api\Objects;

use Billogram\Api\Objects\SingletonObject;

/**
 * Represents a remote object on the Billogram service
 *
 * Implements __get for object-like access to the data of the remote object,
 * or use the 'data' property to access the backing array. The data in this
 * object and all sub-objects should be treated as read-only, the only way to
 * change the remote object is through the 'update' method.
 *
 * If the remote data are changed, the local copy can be updated by
 * the 'refresh' method.
 *
 * The 'delete' method can be used to remove the backing object.
 *
 * See the online documentation for the actual structure of remote objects.
 *
 */
class SimpleObject extends SingletonObject
{
    /**
     * Constructor sets the parent object to use for the resource as well as
     * initial data.
     *
     * @param $api
     * @param $objectClass
     * @param $data
     */
    public function __construct($api, $objectClass, $data)
    {
        $this->api = $api;
        $this->objectClass = $objectClass;
        $this->data = (array) $data;
    }

    /**
     * Makes a DELETE request to the API and removes the resource.
     *
     * @return null
     */
    public function delete()
    {
        $this->api->delete($this->url());

        return null;
    }
}
