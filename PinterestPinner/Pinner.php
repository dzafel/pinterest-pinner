<?php
namespace PinterestPinner;

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
    protected $_login = null;

    /**
     * @var string Pinterest account password
     */
    protected $_password = null;

    /**
     * @var string Board ID where the pin should be added to
     */
    protected $_boardId = null;

    /**
     * @var boolean If true pinterest.com will automatically share new pin on connected facebook account
     */
    protected $_shareFacebook = false;

    /**
     * @var string Newly created pin ID
     */
    protected $_pinId = null;

    /**
     * @var \PinterestPinner\HttpClients\ClientInterface
     */
    protected $_clientInterface = null;

    /*
     * Initialize HTTP Client and set default variables.
     */
    public function __construct(\PinterestPinner\HttpClients\ClientInterface $client = null)
    {   
        $this->_clientInterface = is_object($client) ? $client : new \PinterestPinner\HttpClients\Guzzle;
        
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

        $this->_pinId = (is_array($this->_clientInterface->responseContent) and isset($this->_clientInterface->responseContent['resource_response']['data']['id']))
            ? $this->_clientInterface->responseContent['resource_response']['data']['id']
            : null;

        $this->_clientInterface->responseContent = null;

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
            $response = $this->_clientInterface->_httpRequest(
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
        $this->_clientInterface->loadContentAjax('/resource/BoardPickerBoardsResource/get/?' . http_build_query(array(
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
            isset($this->_clientInterface->responseContent['resource_response']['data']['all_boards'])
            and is_array($this->_clientInterface->responseContent['resource_response']['data']['all_boards'])
        ) {
            foreach ($this->_clientInterface->responseContent['resource_response']['data']['all_boards'] as $board) {
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

        $this->_clientInterface->loadContent('/me/');

        $appJson = $this->_clientInterface->getScriptTagData();
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
     * Try to log in to Pinterest.
     *
     * @throws \PinterestPinner\PinnerException
     */
    protected function _postLogin()
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
        $this->_clientInterface->loadContentAjax('/resource/UserSessionResource/create/', $postData, '/login/');

        // Force reload CSRF token, it's different for logged in user
        $this->_clientInterface->_csrfToken = null;
        $this->_clientInterface->_getCSRFToken('/');

        $this->isLoggedIn = true;

        if (
            isset($this->_clientInterface->responseContent['resource_response']['error'])
            and $this->_clientInterface->responseContent['resource_response']['error']
        ) {
            throw new PinnerException($this->_clientInterface->responseContent['resource_response']['error']);
        } elseif (
            !isset($this->_clientInterface->responseContent['resource_response']['data'])
            or !$this->_clientInterface->responseContent['resource_response']['data']
        ) {
            throw new PinnerException('Unknown error while logging in.');
        }
    }

    /**
     * Try to create a new pin.
     *
     * @throws \PinterestPinner\PinnerException
     */
    protected function _postPin()
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

        $this->_clientInterface->loadContentAjax('/resource/PinResource/create/', $postData, '/');

        if (
            isset($this->_clientInterface->responseContent['resource_response']['error'])
            and $this->_clientInterface->responseContent['resource_response']['error']
        ) {
            throw new PinnerException($this->_clientInterface->responseContent['resource_response']['error']);
        } elseif (
            !isset($this->_clientInterface->responseContent['resource_response']['data']['id'])
            or !$this->_clientInterface->responseContent['resource_response']['data']['id']
        ) {
            throw new PinnerException('Unknown error while creating a pin.');
        }
    }
    
}