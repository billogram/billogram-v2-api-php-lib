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

use Billogram\Api\Objects\SimpleObject;
use Billogram\Api\Exceptions\InvalidFieldValueError;

/**
 * Represents a billogram object on the Billogram service
 *
 * In addition to the basic methods of the SimpleObject remote object class,
 * also provides specialized methods to perform events on billogram objects.
 *
 * See the online documentation for the actual structure of billogram objects.
 *
 */
class BillogramObject extends SimpleObject
{
    /**
     * Makes a POST request to /billogram/{id}/command/{event}.
     *
     * @param $eventName
     * @param null $eventData
     * @return $this
     */
    public function performEvent($eventName, $eventData = null)
    {
        $url = $this->url() . '/command/' . $eventName;
        $response = $this->api->post($url, $eventData);
        $this->data = (array) $response->data;

        return $this;
    }

    /**
     * Stores a manual payment for the billogram.
     *
     * @param $amount
     * @return $this
     */
    public function createPayment($amount)
    {
        return $this->performEvent('payment', array('amount' => $amount));
    }

    /**
     * Creates a credit invoice for the specific amount.
     *
     * @param $amount
     * @return $this
     * @throws \Billogram\Api\Exceptions\InvalidFieldValueError
     */
    public function creditAmount($amount)
    {
        if (!is_numeric($amount) || $amount <= 0)
            throw new InvalidFieldValueError("'amount' must be a positive " .
                "numeric value");

        return $this->performEvent(
            'credit',
            array(
                'mode' => 'amount',
                'amount' => $amount
            )
        );
    }

    /**
     * Creates a credit invoice for the full total amount of the billogram.
     *
     * @return $this
     */
    public function creditFull()
    {
        return $this->performEvent('credit', array('mode' => 'full'));
    }

    /**
     * Creates a credit invoice for the remaining amount of the billogram.
     *
     * @return $this
     */
    public function creditRemaining()
    {
        return $this->performEvent('credit', array('mode' => 'remaining'));
    }

    /**
     * Writes a comment/message at the billogram.
     *
     * @param $message
     * @return $this
     */
    public function sendMessage($message)
    {
        return $this->performEvent('message', array('message' => $message));
    }

    /**
     * Sends the billogram for collection. Requires a collectors-agreement.
     *
     * @return $this
     */
    public function sendToCollector()
    {
        return $this->performEvent('collect');
    }

    /**
     * Sends to billogram to factoring (sell the billogram). Requires a
     * factoring-agreement.
     *
     * @return $this
     */
    public function sendToFactoring()
    {
        return $this->performEvent('sell');
    }

    /**
     * Manually send a reminder if the billogram is overdue.
     *
     * @param null $method
     * @return $this
     * @throws \Billogram\Api\Exceptions\InvalidFieldValueError
     */
    public function sendReminder($method = null)
    {
        if ($method) {
            if (!in_array($method, array('Email', 'Letter')))
                throw new InvalidFieldValueError("'method' must be either " .
                    "'Email' or 'Letter'");

            return $this->performEvent('remind', array('method' => $method));
        }

        return $this->performEvent('remind');
    }

    /**
     * Send an unsent billogram using the method of choice
     *
     * @param $method
     * @return $this
     * @throws \Billogram\Api\Exceptions\InvalidFieldValueError
     */
    public function send($method)
    {
        if (!in_array($method, array('Email', 'Letter', 'Email+Letter')))
            throw new InvalidFieldValueError("'method' must be either " .
                "'Email', 'Letter' or 'Email+Letter'");

        return $this->performEvent('send', array('method' => $method));
    }

    /**
     * Resend a billogram via Email or Letter.
     *
     * @param null $method
     * @return $this
     * @throws \Billogram\Api\Exceptions\InvalidFieldValueError
     */
    public function resend($method = null)
    {
        if ($method) {
            if (!in_array($method, array('Email', 'Letter')))
                throw new InvalidFieldValueError("'method' must be either " .
                    "'Email' or 'Letter'");

            return $this->performEvent('resend', array('method' => $method));
        }

        return $this->performEvent('resend');
    }

    /**
     * Returns the PDF-file content for a specific billogram, invoice
     * or letter document. Will throw a ObjectNotFoundError with message
     * 'Object not available yet' if the PDF has not yet been generated.
     *
     * @param null $letterId
     * @param null $invoiceNo
     * @return string
     * @throws \Billogram\Api\Exceptions\ObjectNotFoundError
     */
    public function getInvoicePdf($letterId = null, $invoiceNo = null)
    {
        $params = array();
        if ($letterId)
            $params['letter_id'] = $letterId;
        if ($invoiceNo)
            $params['invoice_no'] = $invoiceNo;

        $response = $this->api->get(
            $this->url() . '.pdf',
            $params,
            'application/json'
        );

        return base64_decode($response->data->content);
    }

    /**
     * Returns the PDF-file content for the billogram's attachment.
     *
     * @return string
     */
    public function getAttachmentPdf()
    {
        $response = $this->api->get(
            $this->url() . '/attachment.pdf',
            null,
            'application/json'
        );

        return base64_decode($response->data->content);
    }

    /**
     * Attach a PDF to the billogram.
     *
     * @param $filepath
     * @return $this
     */
    public function attachPdf($filepath)
    {
        $content = file_get_contents($filepath);
        $filename = basename($filepath);

        return $this->performEvent('attach', array('filename' => $filename, 'content' => base64_encode($content)));
    }
}
