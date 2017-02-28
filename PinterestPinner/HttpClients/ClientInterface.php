<?php

namespace PinterestPinner\HttpClients;

/*
* Client interface for web requests.
*/

abstract class ClientInterface
{    

    protected $_httpClient = null;
    
    /**
     * @var string Pinterest App version loaded from pinterest.com
     */
    protected $_appVersion = null;

    /**
     * @var string CSRF token loaded from pinterest.com
     */
    protected $_csrfToken = null;
    
    /**
     * @var string Pinterest page loaded content
     */
    public $responseContent = null;
    
    /**
     * @var array Default requests headers
     */
    protected $_httpHeaders = array();
    
    function __construct(){
        // Default HTTP headers for requests
        $this->_httpHeaders = array(
            'Connection' => 'keep-alive',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache',
            'Accept-Language' => 'en-US,en;q=0.5',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML => like Gecko) Iron/31.0.1700.0 Chrome/31.0.1700.0',
        );
        $this->_httpClient = $this->_getClient();
    }
    
    /**
     * Get Pinterest App Version.
     *
     * @return string
     * @throws \PinterestPinner\PinnerException
     */
    protected function _getAppVersion()
    {
        if ($this->_appVersion) {
            return $this->_appVersion;
        }

        if (!$this->responseContent) {
            $this->loadContent('/login/');
        }

        $appJson = $this->responseToArray();
        if ($appJson and isset($appJson['context']['app_version']) and $appJson['context']['app_version']) {
            $this->_appVersion = $appJson['context']['app_version'];
            return $this->_appVersion;
        }

        throw new \PinterestPinner\PinnerException('Error getting App Version from "jsInit1" JSON data.');
    }

    /**
     * Get Pinterest CSRF Token.
     *
     * @param string $url
     * @return string
     * @throws \PinterestPinner\PinnerException
     */
    protected function _getCSRFToken($url = '/login/')
    {
        if ($this->_csrfToken) {
            return $this->_csrfToken;
        }

        if (!$this->responseContent) {
            $this->loadContent($url);
        }

        if (isset($this->_responseHeaders['Set-Cookie'])) {
            if (is_array($this->_responseHeaders['Set-Cookie'])) {
                $content = implode(' ', $this->_responseHeaders['Set-Cookie']);
            } else {
                $content = (string)$this->_responseHeaders['Set-Cookie'];
            }
            preg_match('/csrftoken=(.*)[\b;\s]/isU', $content, $match);
            if (isset($match[1]) and $match[1]) {
                $this->_csrfToken = $match[1];
                return $this->_csrfToken;
            }
        }

        throw new \PinterestPinner\PinnerException('Error getting CSRFToken.');
    }
    
    /**
     * Set cURL url and get the content from curl_exec() call.
     *
     * @param string $url
     * @param array|boolean|null $dataAjax If array - it will be POST request, if TRUE if will be GET, ajax request.
     * @param string $referer
     * @return string
     * @throws \PinterestPinner\PinnerException
     */
    
    public function loadContentAjax($url, $dataAjax = true, $referer = ''){
        if (is_array($dataAjax)) {
            $headers = array_merge($this->_httpHeaders, array(
                'X-NEW-APP' => '1',
                'X-APP-VERSION' => $this->_getAppVersion(),
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'X-CSRFToken' => $this->_getCSRFToken(),
                'Referer' => self::PINTEREST_URL . $referer,
            ));
            $response = $this->_httpRequest('POST', $url, $dataAjax, $headers);
        } elseif ($dataAjax === true) {
            $headers = $this->_httpHeaders;
            
            $headers = array_merge($headers, array(
                'X-NEW-APP' => '1',
                'X-APP-VERSION' => $this->_getAppVersion(),
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'X-Pinterest-AppState' => 'active',
            ));

            $response = $this->_httpRequest('GET', $url, null, $headers);
        }
        $this->_parseJsonResponse($response);
    }
    
    /**
     * Set cURL url and get the content from curl_exec() call.
     *
     * @param string $url
     * @return string
     * @throws \PinterestPinner\PinnerException
     */
    
    public function loadContent($url)
    {
        $response = $this->_httpRequest('GET', $url);
        $this->_parseJsonResponse($response);
    }
    
    
    /**
     * Parse the response from _httpRequest().
     *
     * @param $response
     * @return string
     * @throws \PinterestPinner\PinnerException
     */

    protected function _parseJsonResponse($response){
        $code = (int)substr($this->_getResponseStatusCode($response), 0, 2);
        if ($code !== 20) {
            throw new \PinterestPinner\PinnerException(
                'HTTP error (' . $url . '): ' . $this->_getResponseStatusCode($response) . ' ' . $this->_getResponseStatusMessage($response)
            );
        }

        $this->responseContent = (string)$this->_getResponseBody($response);
        if (substr($this->responseContent, 0, 1) === '{') {
            $this->responseContent = @json_decode($this->responseContent, true);
        }
        $this->_responseHeaders = (array)$this->_getResponseHeaders($response);
    }
    
    /**
     * Get data array from JSON response.
     *
     * @return array|bool
     */
    public function responseToArray()
    {
        if (is_string($this->responseContent)) {
            preg_match(
                '/<script\s*type="application\/json"\s+id=\'jsInit1\'>\s*(\{.+\})\s*<\/script>/isU',
                $this->responseContent,
                $match
            );
            if (isset($match[1]) and $match[1]) {
                $result = @json_decode($match[1], true);
                if (is_array($result)) {
                    return $result;
                }
            }
        }
        return false;
    }
    
    /**
    * Returns the client used for HTTP requests
    **/
    
    abstract protected function _getClient();
    
    /**
     * Make a HTTP request to Pinterest.
     *
     * @param string $type
     * @param string $urlPath
     * @param null|array $data
     * @param array $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract protected function _httpRequest($type = 'GET', $urlPath, $data = null, $headers = array());
    
    abstract protected function _getResponseStatusCode($response);
    
    abstract protected function _getResponseStatusMessage($response);
    
    abstract protected function _getResponseBody($response);
    
    abstract protected function _getResponseHeaders($response);

}