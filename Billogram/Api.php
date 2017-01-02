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

namespace Billogram;

use Billogram\Api\Objects\SingletonObject;
use Billogram\Api\Models\SimpleClass;
use Billogram\Api\Models\BillogramClass;
use Billogram\Api\Exceptions\ServiceMalfunctioningError;
use Billogram\Api\Exceptions\ObjectNotFoundError;
use Billogram\Api\Exceptions\NotAuthorizedError;
use Billogram\Api\Exceptions\InvalidAuthenticationError;
use Billogram\Api\Exceptions\RequestFormError;
use Billogram\Api\Exceptions\PermissionDeniedError;
use Billogram\Api\Exceptions\InvalidFieldValueError;
use Billogram\Api\Exceptions\InvalidFieldCombinationError;
use Billogram\Api\Exceptions\ReadOnlyFieldError;
use Billogram\Api\Exceptions\UnknownFieldError;
use Billogram\Api\Exceptions\InvalidObjectStateError;
use Billogram\Api\Exceptions\RequestDataError;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;

/**
 * Pseudo-connection to the Billogram v2 API
 *
 * Objects of this class provide a call interface to the Billogram v2 HTTP API.
 *
 * @property-read \Billogram\Api\Models\SimpleClass $items
 * @property-read \Billogram\Api\Models\SimpleClass $customers
 * @property-read \Billogram\Api\Models\BillogramClass $billogram
 * @property-read \Billogram\Api\Objects\SingletonObject $settings
 * @property-read \Billogram\Api\Objects\SingletonObject $logotype
 * @property-read \Billogram\Api\Models\SimpleClass $reports
 * @property-read \Billogram\Api\Models\SimpleClass $creditors
 */
class Api
{
    const API_URL_BASE = "https://billogram.com/api/v2";
    const USER_AGENT = "Billogram API PHP Library/1.11";

    private $authUser;
    private $authKey;
    private $apiBase;
    private $userAgent;
    private $extraHeaders;
    private $httpClient;
    private $messageFactory;

    private $itemsConnector;
    private $customersConnector;
    private $billogramConnector;
    private $settingsConnector;
    private $logotypeConnector;
    private $reportsConnector;
    private $creditorsConnector;

    /**
     * Create a Billogram API connection object
     *
     * Pass the API authentication in the auth_user and auth_key parameters.
     * API accounts can only be created from the Billogram web interface.
     */
    public function __construct(
        $authUser,
        $authKey,
        $userAgent = self::USER_AGENT,
        $apiBase = self::API_URL_BASE,
        $extraHeaders = array(),
        HttpClient $httpClient = null,
        MessageFactory $messageFactory = null
    ) {
        $this->authUser = $authUser;
        $this->authKey = $authKey;
        $this->apiBase = $apiBase;
        if ($userAgent)
            $this->userAgent = $userAgent;
        else
            $this->userAgent = self::USER_AGENT;
        if (!$extraHeaders)
            $this->extraHeaders = array();
        else
            $this->extraHeaders = $extraHeaders;
        $this->httpClient = $httpClient ?: HttpClientDiscovery::find();
        $this->messageFactory = $messageFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * Checks the response ($response as a response-object from httpRequest)
     * from the API and throws the appropriate exceptions or returns the
     * de-encoded data.
     *
     */
    private function checkApiResponse($response, $expectContentType = null)
    {
        if ($response->getStatusCode() !== 200 || $expectContentType == null) {
            $expectContentType = 'application/json';
        }

        if (!$response->hasHeader('Content-Type')) {
            throw new ServiceMalfunctioningError('Billogram API did not ' .
                    'return a content type');
        }

        if ($response->getStatusCode() >= 500 && $response->getStatusCode() <= 600) {
            if ($response->getHeader('Content-Type') == $expectContentType &&
                $expectContentType == 'application/json') {
                $data = json_decode((string)$response->getBody());
                throw new ServiceMalfunctioningError('Billogram API reported ' .
                    'a server error: ' . $data->status . ' - ' .
                    $data->data->message);
            }
            throw new ServiceMalfunctioningError('Billogram API reported a ' .
                'server error');
        }

        if ($response->getStatusCode() == 401) {
            throw new InvalidAuthenticationError('The user/key ' .
                'combination is wrong, check the credentials used and ' .
                'possibly generate a new set');
        }

        if ($response->getHeader('Content-Type') != $expectContentType) {
            if ($response->getHeader('Content-Type') == 'application/json') {
                $data = json_decode((string)$response->getBody());
                if ($data->status == 'NOT_AVAILABLE_YET')
                    throw new ObjectNotFoundError('Object not available yet');
                throw new ServiceMalfunctioningError('Billogram API returned ' .
                    'unexpected content type');
            }
        }

        if ($expectContentType == 'application/json') {
            $data = json_decode((string)$response->getBody());
            $status = $data->status;
            if (!$status)
                throw new ServiceMalfunctioningError('Response data missing ' .
                    'status field');
            if (!isset($data->data))
                throw new ServiceMalfunctioningError('Response data missing ' .
                    'data field');
        } else {
            return (string)$response->getBody();
        }

        if ($response->getStatusCode() == 403) {
            if ($status == 'PERMISSION_DENIED')
                throw new NotAuthorizedError('Not allowed to perform the ' .
                    'requested operation');
            elseif ($status == 'INVALID_AUTH')
                throw new InvalidAuthenticationError('The user/key ' .
                    'combination is wrong, check the credentials used and ' .
                    'possibly generate a new set');
            elseif ($status == 'MISSING_AUTH')
                throw new RequestFormError('No authentication data was given');
            else
                throw new PermissionDeniedError('Permission denied, status=' .
                    $status);
        }

        if ($response->getStatusCode() == 404) {
            if ($status == 'NOT_AVAILABLE_YET')
                throw new ObjectNotFoundError('Object not available yet');
            throw new ObjectNotFoundError('Object not found');
        }

        if ($response->getStatusCode() == 405) {
            throw new RequestFormError('Invalid HTTP method');
        }

        if ($status == 'OK')
            return $data;
        else
            $message = $data->data->message;

        if ($status == 'MISSING_QUERY_PARAMETER')
            throw new RequestFormError($message);
        elseif ($status == 'INVALID_QUERY_PARAMETER')
            throw new RequestFormError($message);
        elseif ($status == 'INVALID_PARAMETER')
            throw new InvalidFieldValueError($message);
        elseif ($status == 'INVALID_PARAMETER_COMBINATION')
            throw new InvalidFieldCombinationError($message);
        elseif ($status == 'READ_ONLY_PARAMETER')
            throw new ReadOnlyFieldError($message);
        elseif ($status == 'UNKNOWN_PARAMETER')
            throw new UnknownFieldError($message);
        elseif ($status == 'INVALID_OBJECT_STATE')
            throw new InvalidObjectStateError($message);
        else
            throw new RequestDataError($message);
    }

    /**
     * Opens a socket and makes a request to the url of choice. Returns an
     * object with statusCode, status, content and the received headers.
     *
     */
    private function httpRequest(
        $url,
        $method,
        $body = array(),
        $sendHeaders = array(),
        $timeout = 10
    ) {
        if (is_array($body)) {
            if (in_array($method, array('POST', 'PUT'))) {
                $body = http_build_query($body);
            } elseif ($body && count($body)) {
                $url .= '?' . http_build_query($body);
                $body = null;
            }
        }

        $request = $this->messageFactory->createRequest($method, $url, $sendHeaders, $body);
        $response = $this->httpClient->sendRequest($request);

        return $response;
    }

    /**
     * Returns an Authorization header to be used for the httpRequest method.
     *
     */
    private function authHeader()
    {
        $auth = base64_encode($this->authUser . ":" . $this->authKey);

        return array('Authorization' => 'Basic ' . $auth);
    }

    /**
     * Makes a GET request to an API object.
     * Used for receiving an existing object or a list of resources.
     *
     */
    public function get($objectUrl, $data = null, $expectContentType = null)
    {
        $url = $this->apiBase . '/' . $objectUrl;

        return $this->checkApiResponse(
            $this->httpRequest($url, 'GET', $data, $this->authHeader()),
            $expectContentType
        );
    }

    /**
     * Makes a POST request to an API object.
     * Used to create a new object.
     *
     */
    public function post($objectUrl, $data)
    {
        $url = $this->apiBase . '/' . $objectUrl;

        return $this->checkApiResponse(
            $this->httpRequest(
                $url,
                'POST',
                json_encode($data),
                array_merge(
                    $this->authHeader(),
                    array('Content-type' => 'application/json')
                )
            )
        );
    }

    /**
     * Makes a PUT request to an API object.
     * Used for updating a single existing object.
     *
     */
    public function put($objectUrl, $data)
    {
        $url = $this->apiBase . '/' . $objectUrl;

        return $this->checkApiResponse(
            $this->httpRequest(
                $url,
                'PUT',
                json_encode($data),
                array_merge(
                    $this->authHeader(),
                    array('Content-type' => 'application/json')
                )
            )
        );
    }

    /**
     * Makes a DELETE request to an API object.
     * Used to delete a single existing object.
     *
     */
    public function delete($objectUrl)
    {
        $url = $this->apiBase . '/' . $objectUrl;

        return $this->checkApiResponse(
            $this->httpRequest(
                $url,
                'DELETE',
                null,
                $this->authHeader()
            )
        );
    }

    /**
     * Provide access to the different resource models.
     *
     */
    public function __get($key)
    {
        switch ($key) {
            case 'items':
                if (!$this->itemsConnector)
                    $this->itemsConnector = new SimpleClass($this, 'item', 'item_no');

                return $this->itemsConnector;
            case 'customers':
                if (!$this->customersConnector)
                    $this->customersConnector = new SimpleClass(
                        $this,
                        'customer',
                        'customer_no'
                    );

                return $this->customersConnector;
            case 'billogram':
                if (!$this->billogramConnector)
                    $this->billogramConnector = new BillogramClass($this);

                return $this->billogramConnector;
            case 'settings':
                if (!$this->settingsConnector)
                    $this->settingsConnector = new SingletonObject($this, 'settings');

                return $this->settingsConnector;
            case 'logotype':
                if (!$this->logotypeConnector)
                    $this->logotypeConnector = new SingletonObject($this, 'logotype');

                return $this->logotypeConnector;
            case 'reports':
                if (!$this->reportsConnector)
                    $this->reportsConnector = new SimpleClass(
                        $this,
                        'report',
                        'filename'
                    );

                return $this->reportsConnector;
            case 'creditors':
                if (!$this->creditorsConnector)
                    $this->creditorsConnector = new SimpleClass(
                        $this,
                        'creditor',
                        'id'
                    );

                return $this->creditorsConnector;
            default:
                throw new UnknownFieldError("Invalid parameter: " . $key);
        }
    }
}
