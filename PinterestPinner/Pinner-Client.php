<?php

namespace PinterestPinner\HttpClients;

/*
* Client interface for web requests.
*/

abstract class ClientInterface
{    
    /**
     * Make a HTTP request to Pinterest.
     *
     * @param string $type
     * @param string $urlPath
     * @param null|array $data
     * @param array $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract public function _httpRequest($type = 'GET', $urlPath, $data = null, $headers = array());
    
    abstract public function _getResponseStatusCode($response);
    
    abstract public function _getResponseStatusMessage($response);
    
    abstract public function _getResponseBody($response);
    
    abstract public function _getResponseHeaders($response);

}