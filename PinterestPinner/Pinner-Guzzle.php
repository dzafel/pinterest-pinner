<?php
namespace PinterestPinner;

use GuzzleHttp\Client as GuzzleClient;

/*
* Child class for the PinnerNoWebClient
* uses Guzzle for web requests.
*/

class Pinner_Guzzle extends Pinner
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
    protected function _httpRequest($type = 'GET', $urlPath, $data = null, $headers = array())
    {
        if (!$this->_httpClient) {
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
            $this->_httpClient = new GuzzleClient($config);
        }

        $url = self::PINTEREST_URL . $urlPath;
        if ($type === 'API') {
            $url = self::PINTEREST_API_URL . $urlPath;
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
    
    protected function _getResponseStatusCode($response)
    {
        return $response->getStatusCode();
    }
    
    protected function _getResponseStatusMessage($response)
    {
        return $response->getReasonPhrase();
    }
    
    protected function _getResponseBody($response)
    {
        return $response->getBody();
    }
    
    protected function _getResponseHeaders($response)
    {
        return $response->getHeaders();
    }

}
