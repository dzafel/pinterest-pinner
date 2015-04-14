<?php
namespace PinterestPinner;

use \GuzzleHttp\Client as GuzzleClient;

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
     * @var bool
     */
    public $is_logged_in = false;

    /**
     * @var Pinterest account login
     */
    private $_login = null;

    /**
     * @var Pinterest account password
     */
    private $_password = null;

    /**
     * @var Board ID where the pin should be added to
     */
    private $_board_id = null;

    /**
     * @var If true pinterest.com will automatically share new pin on connected facebook account
     */
    private $_share_facebook = false;

    /**
     * @var Newly created pin ID
     */
    private $_pin_id = null;

    /**
     * @var Pinterest App version loaded from pinterest.com
     */
    private $_app_version = null;

    /**
     * @var Pinterest page loaded content
     */
    private $_response_content = null;

    /**
     * @var CSRF token loaded from pinterest.com
     */
    private $_csrftoken = null;

    /**
     * @var Default requests headers
     */
    private $_http_headers = array();

    /**
     * @var \GuzzleHttp\Client
     */
    private $_http_client = null;

    /**
     * @var \GuzzleHttp\Client
     */
    private $_api_client = null;

    /*
     * Initialize Guzzle Client and set default variables.
     */
    public function __construct()
    {
        // Default HTTP headers for requests
        $this->_http_headers = array(
            'Connection' => 'keep-alive',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache',
            'Accept-Language' => 'en-US,en;q=0.5',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML => like Gecko) Iron/31.0.1700.0 Chrome/31.0.1700.0',
        );
        // Initialize Guzzle Clients
        $this->_http_client = new GuzzleClient(array(
            'base_url' => self::PINTEREST_URL,
            'defaults' => array(
                'headers' => $this->_http_headers,
            ),
        ));
        $this->_api_client = new GuzzleClient(array(
            'base_url' => self::PINTEREST_API_URL,
            'defaults' => array(
                'headers' => $this->_http_headers,
            ),
        ));
    }

    /**
     * Set Pinterest account login.
     *
     * @param string $login
     * @return PinterestPinner\Pinner
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
     * @return PinterestPinner\Pinner
     */
    public function setPassword($password)
    {
        $this->_password = $password;

        return $this;
    }

    /**
     * Set Pinterest board ID to add pin to.
     *
     * @param string $board_id
     * @return PinterestPinner\Pinner
     */
    public function setBoardID($board_id)
    {
        $this->_board_id = $board_id;

        return $this;
    }

    /**
     * Set pin image URL.
     *
     * @param string $image
     * @return PinterestPinner\Pinner
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
     * @return PinterestPinner\Pinner
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
     * @return PinterestPinner\Pinner
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
     * @return PinterestPinner\Pinner
     */
    public function setShareFacebook($share)
    {
        $this->_share_facebook = (bool)$share;

        return $this;
    }

    /**
     * Get newly created pin ID.
     *
     * @return string|boolean
     */
    public function getPinID()
    {
        return $this->_pin_id ? $this->_pin_id : false;
    }

    /**
     * Create a new pin.
     *
     * @return string|boolean
     */
    public function pin()
    {
        // Reset the pin ID
        $this->_pin_id = null;

        $this->_postLogin();
        $this->_postPin();

        $this->_pin_id = isset($this->_response_content['resource_response']['data']['id']) ? $this->_response_content['resource_response']['data']['id'] : null;

        $this->_response_content = null;

        return $this->getPinID();
    }

    /**
     * Set cURL url and get the content from curl_exec() call.
     *
     * @param string $url
     * @param array|null $post_data
     * @param string $referer
     * @return string
     * @throws PinnerException
     */
    private function _loadContent($url, array $post_data = null, $referer = '')
    {
        if ($post_data) {
            
            try {

                $response = $this->_http_client->post($url, array(
                    'headers' => array_merge($this->_http_headers, array(
                        'X-NEW-APP' => '1',
                        'X-APP-VERSION' => $this->_getAppVersion(),
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Accept' => 'application/json, text/javascript, */*; q=0.01',
                        'X-CSRFToken' => $this->_getCSRFToken(),
                        'Referer' => self::PINTEREST_URL . $referer,
                    )),
                    'verify' => false,
                    'cookies' => true,
                    'body' => $post_data,
                ));

            } catch (\Exception $e) {

                return $e;

            }
            
            
            
        } else {
            
            try {
                $response = $this->_http_client->get($url, array(
                    'headers' => $this->_http_headers,
                    'verify' => false,
                    'cookies' => true,
                ));
            } catch (\Exception $e) {
                return $e;
            }

        }

        $code = (int) substr($response->getStatusCode(), 0, 2);
        if ($code !== 20) {
            throw new PinnerException('HTTP error (' . $url . '): ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());
        }

        $this->_response_content = (string) $response->getBody();
        if (substr($this->_response_content, 0, 1) === '{') {
            $this->_response_content = @json_decode($this->_response_content, true);
        }
        $this->_response_headers = (array) $response->getHeaders();
    }

    /**
     * Get Pinterest App Version.
     *
     * @return string
     * @throws PinnerException
     */
    private function _getAppVersion()
    {
        if ($this->_app_version) {
            return $this->_app_version;
        }

        if (!$this->_response_content) {
            try {
                $this->_loadContent('/login/');
            } catch (\Exception $e) {
                return $e;
            }
        }

        if (is_string($this->_response_content)){
            preg_match('/P\.scout\.init\((\{.+\})\);/isU', $this->_response_content, $match);
            if (isset($match[1]) and $match[1]) {
                $app_json = @json_decode($match[1], true);
                if (is_array($app_json) and isset($app_json['context']['app_version']) and $app_json['context']['app_version']) {
                    $this->_app_version = $app_json['context']['app_version'];
                    return $this->_app_version;
                }
            }
        }

        throw new PinnerException('Error getting App Version from P.scout.init() JSON data.');
    }

    /**
     * Get Pinterest CSRF Token.
     *
     * @param string $url
     * @return string
     * @throws PinnerException
     */
    private function _getCSRFToken($url = '/login/')
    {
        if ($this->_csrftoken) {
            return $this->_csrftoken;
        }

        if (!$this->_response_content) {
            try {
                $this->_loadContent($url);
            } catch (\Exception $e) {
                return $e;
            }
        }

        if (isset($this->_response_headers['Set-Cookie'])) {
            if (is_array($this->_response_headers['Set-Cookie'])) {
                $content = implode(' ', $this->_response_headers['Set-Cookie']);
            } else {
                $content = (string) $this->_response_headers['Set-Cookie'];
            }
            preg_match('/csrftoken=(.*)[\b;\s]/isU', $content, $match);
            if (isset($match[1]) and $match[1]) {
                $this->_csrftoken = $match[1];
                return $this->_csrftoken;
            }
        }

        throw new PinnerException('Error getting CSRFToken.');
    }

    /**
     * Try to log in to Pinterest.
     *
     * @throws PinnerException
     */
    private function _postLogin()
    {
        if ($this->is_logged_in) {
            return;
        }

        $post_data = array(
            'data' => json_encode(array(
                'options' => array(
                    'username_or_email' => $this->_login,
                    'password' => $this->_password,
                ),
                'context' => new \stdClass,
            )),
            'source_url' => '/login/',
            'module_path' => 'App()>LoginPage()>Login()>Button(class_name=primary, text=Log In, type=submit, size=large)',
        );

        try {
            $this->_loadContent('/resource/UserSessionResource/create/', $post_data, '/login/');
        } catch (\Exception $e) {
            return $e;
        }

        // Force reload CSRF token, it's different for logged in user
        $this->_csrftoken = null;
        $this->_getCSRFToken('/');

        $this->is_logged_in = true;

        if (isset($this->_response_content['resource_response']['error']) and $this->_response_content['resource_response']['error']) {
            throw new PinnerException($this->_response_content['resource_response']['error']);
        } else {
            if (!isset($this->_response_content['resource_response']['data']) or !$this->_response_content['resource_response']['data']) {
                throw new PinnerException('Unknown error while logging in.');
            }
        }
    }

    /**
     * Try to create a new pin.
     *
     * @throws PinnerException
     */
    private function _postPin()
    {
        $post_data = array(
            'data' => json_encode(array(
                'options' => array(
                    'board_id' => $this->_board_id,
                    'description' => $this->_description,
                    'link' => $this->_link,
                    'share_facebook' => $this->_share_facebook,
                    'image_url' => $this->_image,
                    'method' => 'scraped',
                ),
                'context' => new \stdClass,
            )),
            'source_url' => '/',
            'module_path' => 'App()>ImagesFeedPage(resource=FindPinImagesResource(url=' . $this->_link . '))>Grid()>GridItems()>Pinnable(url=' . $this->_image . ', type=pinnable, link=' . $this->_link . ')#Modal(module=PinCreate())',
        );

        try {
            $this->_loadContent('/resource/PinResource/create/', $post_data, '/');
        } catch (\Exception $e) {
            return $e;
        }

        if (isset($this->_response_content['resource_response']['error']) and $this->_response_content['resource_response']['error']) {
            throw new PinnerException($this->_response_content['resource_response']['error']);
        } else {
            if (!isset($this->_response_content['resource_response']['data']['id']) or !$this->_response_content['resource_response']['data']['id']) {
                throw new PinnerException('Unknown error while creating a pin.');
            }
        }
    }

    /**
     * Get user's pins.
     *
     * @param $board_id
     * @return array
     * @throws PinnerException
     */
    public function getPins($board_id = null)
    {
        
        try {
            $response = $this->_api_client->get('/v3/pidgets/users/' . urlencode($this->_login) . '/pins/', array(
                'headers' => $this->_http_headers,
                'verify' => false,
            ));
        } catch (\Exception $e) {
            return $e;
        }
        
        if ($response->getStatusCode() === 200) {
            $collection = $response->json();
            if (isset($collection['data']['pins'])) {
                if ($board_id) {
                    $pins = array();
                    foreach ($collection['data']['pins'] as $pin) {
                        if ($pin['board']['id'] == $board_id) {
                            $pins[] = $pin;
                        }
                    }
                    return $pins;
                }
                return $collection['data']['pins'];
            }
            return array();
        }
        throw new PinnerException('Unknown error while getting pins list.');
    }

    /**
     * Get user's boards.
     *
     * @return array
     * @throws PinnerException
     */
    public function getBoards()
    {
        $pins = $this->getPins();
        $boards = array();
        if (isset($pins['data']['pins'])) {
            foreach ($pins['data']['pins'] as $pin) {
                $boards[$pin['board']['id']] = $pin['board']['name'];
            }
        }
        return $boards;
    }

    /**
     * Get logged in user data.
     *
     * @return mixed
     * @throws PinnerException
     */
    public function getUserData()
    {

        try {
            $this->_postLogin();
            $this->_loadContent('/me/');
        } catch (\Exception $e) {
                return $e;
        }

        if (is_string($this->_response_content)){
            preg_match('/P\.start\.start\((\{.+\})\);/isU', $this->_response_content, $match);
            if (isset($match[1]) and $match[1]) {
                $app_json = @json_decode($match[1], true);
                if (isset($app_json['resourceDataCache'][0]['data'])) {
                    if (isset($app_json['resourceDataCache'][0]['data']['repins_from'])) {
                        unset($app_json['resourceDataCache'][0]['data']['repins_from']);
                    }
                    return $app_json['resourceDataCache'][0]['data'];
                }
            }
        }

        throw new PinnerException('Unknown error while getting user data.');
    }
}
