<?php
namespace PinterestPinner\HttpClients;

use GuzzleHttp\Client as GuzzleClient;

/*
* uses Guzzle for web requests.
*/

class Guzzle extends ClientInterface
{    
    
    var $config = null;

    public function _getClient(){
        if (version_compare(GuzzleClient::VERSION, '6.0.0', '>=')) {
            $config = array(
                'headers' => $this->_httpHeaders,
                'cookies' => true,
                'verify' => false,
            );
        } else {
            $config = array(
                'defaults' => array(
                    'headers' => $this->_httpHeaders,
                ),
            );
        }
        return new GuzzleClient($config);
    }
    
    /**
     * Make a HTTP request to Pinterest.
     *
     * @param string $type
     * @param string $urlPath
     * @param null|array $data
     * @param array $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function _httpRequest($type = 'GET', $urlPath, $data = null, $headers = array())
    {

        $url = \PinterestPinner\Pinner::PINTEREST_URL . $urlPath;
        if ($type === 'API') {
            $url = \PinterestPinner\Pinner::PINTEREST_API_URL . $urlPath;
            $type = 'GET';
        }

        if (empty($headers)) {
            $headers = $this->_httpHeaders;
        }

        if ($type === 'POST') {
            if (version_compare(GuzzleClient::VERSION, '6.0.0', '>=')) {
                $response = $this->_httpClient->request('POST', $url, array(
                    'form_params' => $data,
                    'headers' => $headers,
                ));
            } else {
                $response = $this->_httpClient->post($url, array(
                    'headers' => $headers,
                    'verify' => false,
                    'cookies' => true,
                    'body' => $data,
                ));
            }
        } else {
            if (version_compare(GuzzleClient::VERSION, '6.0.0', '>=')) {
                $response = $this->_httpClient->request('GET', $url, array(
                    'headers' => $headers,
                ));
            } else {
                $response = $this->_httpClient->get($url, array(
                    'headers' => $headers,
                    'verify' => false,
                    'cookies' => true,
                ));
            }
        }

        return $response;
    }
    
    public function _getResponseStatusCode($response)
    {
        return $response->getStatusCode();
    }
    
    public function _getResponseStatusMessage($response)
    {
        return $response->getReasonPhrase();
    }
    
    public function _getResponseBody($response)
    {
        return $response->getBody();
    }
    
    public function _getResponseHeaders($response)
    {
        return $response->getHeaders();
    }

}
