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
 * @author Jon Gotlin <jon@jon.se>
 */

namespace Billogram\Api\Event;

use Symfony\Component\EventDispatcher\Event;

class RequestEvent extends Event
{
    const REQUEST = 'billogram.request';

    /**
     * @var string
     */
    protected $url;

    /**
     * @var integer
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @param string $url
     * @param int $statusCode
     * @param string $status
     * @param string $content
     * @param array $headers
     */
    public function __construct($url, $statusCode, $status, $content, array $headers)
    {
        $this->url = $url;
        $this->statusCode = $statusCode;
        $this->status = $status;
        $this->content = $content;
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
