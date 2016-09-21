<?php
namespace PinterestPinner;

use GuzzleHttp\Client as GuzzleClient;
use stdClass;

/**
 * Programmatically create a Pinterest's pin.
 *
 * @author  Paweł Ciesielski
 * @see     https://github.com/dzafel/pinterest-pinner
 * @license GPLv2
 */
class Pinner
{
    /**
     * Pinterest.com base URL
     */
    const PINTEREST_URL = 'https://www.pinterest.com';

    /**
     * Pinterest.com base URL
     */
    const PINTEREST_API_URL = 'https://api.pinterest.com';

    /**
     * @var boolean
     */
    public $isLoggedIn = false;

    /**
     * @var array
     */
    public $userData = array();

    /**
     * @var array
     */
    public $boards = array();

    /**
     * @var string Pinterest account login
     */
    private $_login = null;

    /**
     * @var string Pinterest account password
     */
    private $_password = null;

    /**
     * @var string Board ID where the pin should be added to
     */
    private $_boardId = null;

    /**
     * @var boolean If true pinterest.com will automatically share new pin on connected facebook account
     */
    private $_shareFacebook = false;

    /**
     * @var string Newly created pin ID
     */
    private $_pinId = null;

    /**
     * @var string Pinterest App version loaded from pinterest.com
     */
    private $_appVersion = null;

    /**
     * @var string CSRF token loaded from pinterest.com
     */
    private $_csrfToken = null;

    /**
     * @var array Default requests headers
     */
    private $_httpHeaders = array();

    /**
     * @var \GuzzleHttp\Client
     */
    private $_httpClient = null;

    /**
     * @var string Pinterest page loaded content
     */
    protected $_responseContent = null;

    /*
     * Initialize Guzzle Client and set default variables.
     */
    public function __construct()
    {
        // Default HTTP headers for requests
        $this->_httpHeaders = array(
            'Connection' => 'keep-alive',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache',
            'Accept-Language' => 'en-US,en;q=0.5',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML => like Gecko) Iron/31.0.1700.0 Chrome/31.0.1700.0',
        );
    }

    /**
     * Set Pinterest account login.
     *
     * @param string $login
     * @return \PinterestPinner\Pinner
     */
    public function setLogin($login)
    {
        $this->_login = $login;

        return $this;
    }

    /**
     * Set Pinterest account password.
     *
     * @param string $password
     * @return \PinterestPinner\Pinner
     */
    public function setPassword($password)
    {
        $this->_password = $password;

        return $this;
    }

    /**
     * Set Pinterest board ID to add pin to.
     *
     * @param string $boardId
     * @return \PinterestPinner\Pinner
     */
    public function setBoardID($boardId)
    {
        $this->_boardId = $boardId;

        return $this;
    }

    /**
     * Set pin image URL.
     *
     * @param string $image
     * @return \PinterestPinner\Pinner
     */
    public function setImage($image)
    {
        $this->_image = $image;

        return $this;
    }

    /**
     * Set pin description.
     *
     * @param string $description
     * @return \PinterestPinner\Pinner
     */
    public function setDescription($description)
    {
        $this->_description = $description;

        return $this;
    }

    /**
     * Set pin link.
     *
     * @param string $link
     * @return \PinterestPinner\Pinner
     */
    public function setLink($link)
    {
        $this->_link = $link;

        return $this;
    }

    /**
     * Set 'Share on Facebook' option.
     *
     * @param boolean $share
     * @return \PinterestPinner\Pinner
     */
    public function setShareFacebook($share)
    {
        $this->_shareFacebook = (bool)$share;

        return $this;
    }

    /**
     * Get newly created pin ID.
     *
     * @return string|boolean
     */
    public function getPinID()
    {
        return $this->_pinId ?: false;
    }

    /**
     * Create a new pin.
     *
     * @return string|boolean
     */
    public function pin()
    {
        // Reset the pin ID
        $this->_pinId = null;

        $this->_postLogin();
        $this->_postPin();

        $this->_pinId = (is_array($this->_responseContent) and isset($this->_responseContent['resource_response']['data']['id']))
            ? $this->_responseContent['resource_response']['data']['id']
            : null;

        $this->_responseContent = null;

        return $this->getPinID();
    }

    /**
     * Get user's pins.
     *
     * @param $boardId
     * @return array
     * @throws \PinterestPinner\PinnerException
     */
    public function getPins($boardId = null)
    {
        $userData = $this->getUserData();
        if (isset($userData['username'])) {
            $response = $this->_guzzleRequest(
                'API',
                '/v3/pidgets/users/' . urlencode($userData['username']) . '/pins/'
            );
            if ($response->getStatusCode() === 200) {
                $collection = $response->json();
                if (isset($collection['data']['pins'])) {
                    if ($boardId) {
                        $pins = array();
                        foreach ($collection['data']['pins'] as $pin) {
                            if ($pin['board']['id'] == $boardId) {
                                $pins[] = $pin;
                            }
                        }
                        return $pins;
                    }
                    return $collection['data']['pins'];
                }
                return array();
            }
        }
        throw new PinnerException('Unknown error while getting pins list.');
    }

    /**
     * Get user's boards.
     *
     * @return array
     * @throws \PinterestPinner\PinnerException
     */
    public function getBoards()
    {
        if (count($this->boards)) {
            return $this->boards;
        }
        $userData = $this->getUserData();
        if (!isset($userData['username'])) {
            throw new PinnerException('Missing username in user data.');
        }
        $this->_loadContent('/resource/BoardPickerBoardsResource/get/?' . http_build_query(array(
                'source_url' => '/' . $userData['username'] . '/',
                'data' => json_encode(array(
                    'options' => array(
                        'allow_stale' => true,
                        'field_set_key' => 'board_picker',
                        'filter' => 'all',
                        'shortlist_length' => 1,
                    ),
                    'context' => new stdClass,
                )),
                'module_path' => 'App>FooterButtons>DropdownButton>Dropdown>AddPin>ShowModalButton(module=PinUploader)'
                    . '#Modal(showCloseModal=true, mouseDownInModal=false)',
                '_' => time() . '999',
            )), true);
        $this->boards = array();
        if (
            isset($this->_responseContent['resource_response']['data']['all_boards'])
            and is_array($this->_responseContent['resource_response']['data']['all_boards'])
        ) {
            foreach ($this->_responseContent['resource_response']['data']['all_boards'] as $board) {
                if (isset($board['id'], $board['name'])) {
                    $this->boards[$board['id']] = $board['name'];
                }
            }
        }
        return $this->boards;
    }

    /**
     * Get logged in user data.
     *
     * @return mixed
     * @throws \PinterestPinner\PinnerException
     */
    public function getUserData()
    {
        if (count($this->userData)) {
            return $this->userData;
        }

        $this->_postLogin();

        $this->_loadContent('/me/');

        $appJson = $this->_responseToArray();
        if (
            $appJson
            and isset($appJson['tree']['data'], $appJson['tree']['data']['username'])
        ) {
            $this->userData = $appJson['tree']['data'];
            return $this->userData;
        }

        throw new PinnerException('Unknown error while getting user data.');
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
    protected function _loadContent($url, $dataAjax = null, $referer = '')
    {
        if (is_array($dataAjax)) {
            $headers = array_merge($this->_httpHeaders, array(
                'X-NEW-APP' => '1',
                'X-APP-VERSION' => $this->_getAppVersion(),
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'X-CSRFToken' => $this->_getCSRFToken(),
                'Referer' => self::PINTEREST_URL . $referer,
            ));
            $response = $this->_guzzleRequest('POST', $url, $dataAjax, $headers);
        } else {
            $headers = $this->_httpHeaders;
            if ($dataAjax === true) {
                $headers = array_merge($headers, array(
                    'X-NEW-APP' => '1',
                    'X-APP-VERSION' => $this->_getAppVersion(),
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Accept' => 'application/json, text/javascript, */*; q=0.01',
                    'X-Pinterest-AppState' => 'active',
                ));
            }
            $response = $this->_guzzleRequest('GET', $url, null, $headers);
        }

        $code = (int)substr($response->getStatusCode(), 0, 2);
        if ($code !== 20) {
            throw new PinnerException(
                'HTTP error (' . $url . '): ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase()
            );
        }

        $this->_responseContent = (string)$response->getBody();
        if (substr($this->_responseContent, 0, 1) === '{') {
            $this->_responseContent = @json_decode($this->_responseContent, true);
        }
        $this->_responseHeaders = (array)$response->getHeaders();
    }

    /**
     * Get Pinterest App Version.
     *
     * @return string
     * @throws \PinterestPinner\PinnerException
     */
    private function _getAppVersion()
    {
        if ($this->_appVersion) {
            return $this->_appVersion;
        }

        if (!$this->_responseContent) {
            $this->_loadContent('/login/');
        }

        $appJson = $this->_responseToArray();
        if ($appJson and isset($appJson['context']['app_version']) and $appJson['context']['app_version']) {
            $this->_appVersion = $appJson['context']['app_version'];
            return $this->_appVersion;
        }

        throw new PinnerException('Error getting App Version from "jsInit1" JSON data.');
    }

    /**
     * Get Pinterest CSRF Token.
     *
     * @param string $url
     * @return string
     * @throws \PinterestPinner\PinnerException
     */
    private function _getCSRFToken($url = '/login/')
    {
        if ($this->_csrfToken) {
            return $this->_csrfToken;
        }

        if (!$this->_responseContent) {
            $this->_loadContent($url);
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

        throw new PinnerException('Error getting CSRFToken.');
    }

    /**
     * Try to log in to Pinterest.
     *
     * @throws \PinterestPinner\PinnerException
     */
    private function _postLogin()
    {
        if ($this->isLoggedIn) {
            return;
        }

        $postData = array(
            'data' => json_encode(array(
                'options' => array(
                    'username_or_email' => $this->_login,
                    'password' => $this->_password,
                ),
                'context' => new stdClass,
            )),
            'source_url' => '/login/',
            'module_path' => 'App()>LoginPage()>Login()>Button(class_name=primary, '
                . 'text=Log In, type=submit, size=large)',
        );
        $this->_loadContent('/resource/UserSessionResource/create/', $postData, '/login/');

        // Force reload CSRF token, it's different for logged in user
        $this->_csrfToken = null;
        $this->_getCSRFToken('/');

        $this->isLoggedIn = true;

        if (
            isset($this->_responseContent['resource_response']['error'])
            and $this->_responseContent['resource_response']['error']
        ) {
            throw new PinnerException($this->_responseContent['resource_response']['error']);
        } elseif (
            !isset($this->_responseContent['resource_response']['data'])
            or !$this->_responseContent['resource_response']['data']
        ) {
            throw new PinnerException('Unknown error while logging in.');
        }
    }

    /**
     * Try to create a new pin.
     *
     * @throws \PinterestPinner\PinnerException
     */
    private function _postPin()
    {
        $postData = array(
            'data' => json_encode(array(
                'options' => array(
                    'board_id' => $this->_boardId,
                    'description' => $this->_description,
                    'link' => $this->_link,
                    'share_facebook' => $this->_shareFacebook,
                    'image_url' => $this->_image,
                    'method' => 'scraped',
                ),
                'context' => new stdClass,
            )),
            'source_url' => '/',
            'module_path' => 'App()>ImagesFeedPage(resource=FindPinImagesResource(url='
                . $this->_link . '))>Grid()>GridItems()>Pinnable(url=' . $this->_image
                . ', type=pinnable, link=' . $this->_link . ')#Modal(module=PinCreate())',
        );

        $this->_loadContent('/resource/PinResource/create/', $postData, '/');

        if (
            isset($this->_responseContent['resource_response']['error'])
            and $this->_responseContent['resource_response']['error']
        ) {
            throw new PinnerException($this->_responseContent['resource_response']['error']);
        } elseif (
            !isset($this->_responseContent['resource_response']['data']['id'])
            or !$this->_responseContent['resource_response']['data']['id']
        ) {
            throw new PinnerException('Unknown error while creating a pin.');
        }
    }

    /**
     * Get data array from JSON response.
     *
     * @return array|bool
     */
    private function _responseToArray()
    {
        if (is_string($this->_responseContent)) {
            preg_match(
                '/<script\s*type="application\/json"\s+id=\'jsInit1\'>\s*(\{.+\})\s*<\/script>/isU',
                $this->_responseContent,
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
     * Make a HTTP request to Pinterest.
     *
     * @param string $type
     * @param string $urlPath
     * @param null|array $data
     * @param array $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function _guzzleRequest($type = 'GET', $urlPath, $data = null, $headers = array())
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
}
