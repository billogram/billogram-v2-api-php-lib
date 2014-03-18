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

use Billogram\Api\Models\SimpleClass;
use Billogram\Api\Exceptions\InvalidFieldValueError;

/**
 * Represents the collection of billogram objects on the Billogram service
 *
 * In addition to the methods of the SimpleClass collection wrapper, also provides
 * specialized creation methods to create billogram objects and state transition them
 * immediately.
 *
 */
class BillogramClass extends SimpleClass
{
    public $objectClass = 'Billogram\Api\Objects\BillogramObject';

    /**
     * Constructor sets the base url and significant id field for the resource.
     *
     */
    public function __construct($api)
    {
        $this->api = $api;
        $this->urlName = 'billogram';
        $this->objectIdField = 'id';
    }

    /**
     * Makes a POST request to the API and creates a new object.
     *
     * @param $data
     * @return \Billogram\Api\Objects\BillogramObject
     */
    public function create($data)
    {
        return parent::create($data);
    }

    /**
     * Creates and sends a billogram using the $data and $method supplied.
     *
     * @param $data
     * @param $method
     * @throws \Billogram\Api\Exceptions\InvalidFieldValueError
     * @return \Billogram\Api\Objects\BillogramObject
     */
    public function createAndSend($data, $method)
    {
        if (!in_array($method, array('Email', 'Letter', 'Email+Letter')))
            throw new InvalidFieldValueError("Invalid method, should be 'Email', 'Letter' or 'Email+Letter'");
        $billogram = $this->create($data);
        try {
            $billogram->send($method);
        } catch (InvalidFieldValueError $e) {
            $billogram->delete();
            throw $e;
        }

        return $billogram;
    }

    /**
     * Creates and sells a billogram.
     *
     * @param $data
     * @param $method
     * @return \Billogram\Api\Objects\BillogramObject
     */
    public function createAndSell($data, $method)
    {
        $data['_event'] = 'sell';
        $billogram = $this->create($data);

        return $billogram;
    }
}
