<?php
/**
 * Programmatically create a Pinterest's pin.
 *
 * @author  Paweł Ciesielski
 * @see     https://github.com/dzafel/pinterest-pinner
 * @license GPLv2
 */

if (!class_exists('PinterestPinner')) {

    class PinterestPinner {

        const VERSION = '1.0.1';

        const PINTEREST_URL = 'https://www.pinterest.com/';
        const PINTEREST_LOGIN_PATH = 'login/';
        const PINTEREST_LOGIN_POST_PATH = 'resource/UserSessionResource/create/';
        const PINTEREST_PIN_POST_PATH = 'resource/PinResource/create/';

        private $_login = null;
        private $_password = null;
        private $_board_id = null;
        private $_share_facebook = false;
        private $_pin_id = null;
        private $_curl = null;
        private $_cookie_file = null;
        private $_content = null;
        private $_app_version = null;
        private $_csrftoken = null;
        private $_http_headers = array();
        private $_error = null;
        public $is_logged_in = false;
        public $user_resources = array();

        public function __construct($login = null, $password = null, array $curl_options = array())
        {
            if ($login !== null) {
                $this->setLogin($login);
            }

            if ($password !== null) {
                $this->setPassword($password);
            }

            $this->_http_headers = array(
                'Connection: keep-alive',
                'Pragma: no-cache',
                'Cache-Control: no-cache',
                'Accept-Language: en-US,en;q=0.5',
            );

            // Create a temp file for cookie storage.
            $this->_cookie_file = array_search('uri', @array_flip(stream_get_meta_data($GLOBALS[mt_rand()] = tmpfile())));
            if (!$this->_cookie_file or !is_writeable($this->_cookie_file)) {
                // If tmpfile() fails use txt file in /temp dir
                $this->_cookie_file = (function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : '/tmp') . '/PinterestPinner.tmp';
                file_put_content($this->_cookie_file, '');
            }

            $curl_options += array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_REFERER        => self::PINTEREST_URL . self::PINTEREST_LOGIN_PATH,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER         => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_ENCODING       => 'gzip,deflate',
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML => like Gecko) Iron/31.0.1700.0 Chrome/31.0.1700.0',
                CURLOPT_VERBOSE        => true,
                CURLOPT_HTTPHEADER     => $this->_http_headers,
                CURLOPT_COOKIEFILE     => $this->_cookie_file,
                CURLOPT_COOKIEJAR      => $this->_cookie_file,
            );

            $this->_curl = curl_init();
            curl_setopt_array($this->_curl, $curl_options);
        }

        /**
         * Delete cookie file.
         */
        public function __destruct()
        {
            if ($this->_cookie_file and is_file($this->_cookie_file)) {
                @unlink($this->_cookie_file);
            }
        }

        /**
         * Set Pinterest account login.
         *
         * @param  string          $login
         * @return PinterestPinner $this
         */
        public function setLogin($login)
        {
            $this->_login = $login;

            return $this;
        }

        /**
         * Set Pinterest account password.
         *
         * @param  string          $password
         * @return PinterestPinner $this
         */
        public function setPassword($password)
        {
            $this->_password = $password;

            return $this;
        }

        /**
         * Set Pinterest board ID to add pin to.
         *
         * @param  string          $board_id
         * @return PinterestPinner $this
         */
        public function setBoardID($board_id)
        {
            $this->_board_id = $board_id;

            return $this;
        }

        /**
         * Set pin image URL.
         *
         * @param  string          $image
         * @return PinterestPinner $this
         */
        public function setImage($image)
        {
            $this->_image = $image;

            return $this;
        }

        /**
         * Set pin description.
         *
         * @param  string          $description
         * @return PinterestPinner $this
         */
        public function setDescription($description)
        {
            $this->_description = $description;

            return $this;
        }

        /**
         * Set pin link.
         *
         * @param  string          $link
         * @return PinterestPinner $this
         */
        public function setLink($link)
        {
            $this->_link = $link;

            return $this;
        }

        /**
         * Set 'Share on Facebook' option.
         *
         * @param  boolean         $share
         * @return PinterestPinner $this
         */
        public function setShareFacebook($share)
        {
            $this->_share_facebook = (bool) $share;

            return $this;
        }

        /**
         * Create a new pin.
         *
         * @param  string  $board_id
         * @param  string  $image
         * @param  string  $description
         * @param  string  $link
         * @param  boolean $share_facebook
         * @return boolean
         */
        public function pin($board_id = null, $image = null, $description = null, $link = null, $share_facebook = false)
        {
            if (is_array($board_id)) {
                if (isset($board_id['image']) and $board_id['image']) {
                    $image = $board_id['image'];
                }
                if (isset($board_id['description']) and $board_id['description']) {
                    $description = $board_id['description'];
                }
                if (isset($board_id['link']) and $board_id['link']) {
                    $link = $board_id['link'];
                }
                if (isset($board_id['share_facebook']) and $board_id['share_facebook']) {
                    $share_facebook = (bool) $board_id['share_facebook'];
                }
                if (isset($board_id['board']) and $board_id['board']) {
                    $board_id = $board_id['board'];
                }
                else if (isset($board_id['board_id']) and $board_id['board_id']) {
                    $board_id = $board_id['board_id'];
                }
            }

            if ($board_id !== null) {
                $this->setBoardID($board_id);
            }
            if ($image !== null) {
                $this->setImage($image);
            }
            if ($description !== null) {
                $this->setDescription($description);
            }
            if ($link !== null) {
                $this->setLink($link);
            }
            if ($share_facebook !== null) {
                $this->setShareFacebook($share_facebook);
            }

            $this->_pin_id = null;

            try {
                $this->_postLogin();
                $this->_postPin();

                $this->_pin_id = $this->_content['resource_response']['data']['id'];

                $this->_content = null;
            }
            catch (PinterestPinnerException $e) {
                $this->_error = $e->getMessage();
            }

            return (bool) $this->_pin_id;
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
         * Get an error message of last pin() call.
         *
         * @return string
         */
        public function getError()
        {
            return $this->_error;
        }

        /**
         * Set cURL url and get the content from curl_exec() call.
         *
         * @return string $url
         * @return string
         */
        private function _getContent($url)
        {
            curl_setopt($this->_curl, CURLOPT_URL, $url);
            $this->_content = curl_exec($this->_curl);
            return $this->_content;
        }

        /**
         * Get Pinterest App Version.
         *
         * @return string
         * @throws PinterestPinnerException
         */
        private function _getAppVersion()
        {
            if ($this->_app_version) {
                return $this->_app_version;
            }

            if (!$this->_content) {
                $this->_getContent(self::PINTEREST_URL . self::PINTEREST_LOGIN_PATH);
            }

            preg_match('/P\.scout\.init\((\{.+\})\);/isU', $this->_content, $match);
            if (isset($match[1]) and $match[1]) {
                $app_json = json_decode($match[1], true);
                if (isset($app_json['context']['app_version']) and $app_json['context']['app_version']) {
                    $this->_app_version = $app_json['context']['app_version'];
                    return $this->_app_version;
                }
            }

            throw new PinterestPinnerException('Error getting P.scout.init() JSON data.');
        }

        /**
         * Get Pinterest CSRF Token.
         *
         * @return string
         * @throws PinterestPinnerException
         */
        private function _getCSRFToken()
        {
            if ($this->_csrftoken) {
                return $this->_csrftoken;
            }

            if (!$this->_content) {
                $this->_getContent(self::PINTEREST_URL . self::PINTEREST_LOGIN_PATH);
            }

            preg_match('/csrftoken=(.*)[\b;\s]/isU', $this->_content, $match);
            if (isset($match[1]) and $match[1]) {
                $this->_csrftoken = $match[1];
                return $this->_csrftoken;
            }

            throw new PinterestPinnerException('Error getting CSRFToken.');
        }
        
        public function isLoggedIn(){
            
            $logged = false;
            
            try {
                $this->_postLogin();
                $logged = $this->is_logged_in;
            }
            catch (PinterestPinnerException $e) {
                $this->_error = $e->getMessage();
            }
            
            return $logged;
            
        }

        /**
         * Try to log in to Pinterest.
         *
         * @throws PinterestPinnerException
         */
        private function _postLogin()
        {
            if ($this->is_logged_in === true) return;
            
            $post_data = array(
                'data' => json_encode(array(
                    'options' => array(
                        'username_or_email' => $this->_login,
                        'password' => $this->_password,
                    ),
                    'context' => new stdClass,
                )),
                'source_url' => '/login/',
                'module_path' => 'App()>LoginPage()>Login()>Button(class_name=primary, text=Log In, type=submit, size=large)',
            );
            curl_setopt_array($this->_curl, array(
                CURLOPT_HTTPHEADER => array_merge($this->_http_headers, array(
                    'X-NEW-APP: 1',
                    'X-APP-VERSION: ' . $this->_getAppVersion(),
                    'X-Requested-With: XMLHttpRequest',
                    'Accept: application/json, text/javascript, */*; q=0.01',
                    'X-CSRFToken: ' . $this->_getCSRFToken(),
                )),
                CURLOPT_URL        => self::PINTEREST_URL . self::PINTEREST_LOGIN_POST_PATH,
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_REFERER    => self::PINTEREST_URL . self::PINTEREST_LOGIN_PATH,
                CURLOPT_HEADER     => true,
            ));
            $this->_content = curl_exec($this->_curl);

            $this->_csrftoken = null;
            $this->_getCSRFToken();

            $http_code = (int) curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
            if ($http_code === 200) {
                $this->_content = explode("\n{\"", $this->_content);
                $this->_content = '{"' . array_pop($this->_content);
                $this->_content = json_decode($this->_content, true);
            }

            if (isset($this->_content['resource_response']['error']) and $this->_content['resource_response']['error']) {
                throw new PinterestPinnerException($this->_content['resource_response']['error']);
            }
            else if (!isset($this->_content['resource_response']['data']) or !$this->_content['resource_response']['data']) {
                throw new PinterestPinnerException('Unknown error while logging in.');
            }else{
                $this->is_logged_in = true;
            }
        }

        /**
         * Try to create a new pin.
         *
         * @throws PinterestPinnerException
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
                    'context' => new stdClass,
                )),
                'source_url' => '/',
                'module_path' => 'App()>ImagesFeedPage(resource=FindPinImagesResource(url=' . $this->_link . '))>Grid()>GridItems()>Pinnable(url=' . $this->_image . ', type=pinnable, link=' . $this->_link . ')#Modal(module=PinCreate())',
            );
            curl_setopt_array($this->_curl, array(
                CURLOPT_HTTPHEADER => array_merge($this->_http_headers, array(
                    'X-NEW-APP: 1',
                    'X-APP-VERSION: ' . $this->_getAppVersion(),
                    'X-Requested-With: XMLHttpRequest',
                    'Accept: application/json, text/javascript, */*; q=0.01',
                    'X-CSRFToken: ' . $this->_getCSRFToken(),
                )),
                CURLOPT_URL        => self::PINTEREST_URL . self::PINTEREST_PIN_POST_PATH,
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_REFERER    => self::PINTEREST_URL,
            ));
            $this->_content = curl_exec($this->_curl);

            $http_code = (int) curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
            if ($http_code === 200) {
                $this->_content = json_decode($this->_content, true);
            }

            if (isset($this->_content['resource_response']['error']) and $this->_content['resource_response']['error']) {
                throw new PinterestPinnerException($this->_content['resource_response']['error']);
            }
            else if (!isset($this->_content['resource_response']['data']['id']) or !$this->_content['resource_response']['data']['id']) {
                throw new PinterestPinnerException('Unknown error while creating a pin.');
            }
        }
        
        /**
         * Get all pins for a board.
         * @param type $board - like in getUserBoards()
         * @param type $max - maximum number of pins to get
         * @param type $stop_at_pin_id - stop if that pin ID is met.  This pin ID could be saved somewhere and compared when getBoardPins is executed later.
         * @return \Exception
         */
        
        public function getBoardPins($board,$max=0,$stop_at_pin_id=null){
            $bookmark = null;
            $board_page = 0;
            $board_pins = array();
            
            while ($bookmark != '-end-') { //end loop when bookmark "-end-" is returned by pinterest
                try {
                    
                    $query = $this->getPageBoardPins($board,$bookmark);
                    
                    $bookmark = $query['bookmark'];
                    
                    if (isset($query['pins'])){

                        $page_pins = $query['pins'];

                        //stop if this pin ID is found
                        if ($stop_at_pin_id){
                            foreach($page_pins as $key=>$pin){
                                if (isset($pin['id']) && $pin['id']==$stop_at_pin_id){
                                    $page_pins = array_slice($page_pins, 0, $key+1);
                                    $bookmark = '-end-';
                                    break;
                                }
                            }
                        }

                        $board_pins = array_merge($board_pins,$page_pins);

                        //limit reached
                        if ( ($max) && ( count($board_pins)> $max) ){
                            $board_pins = array_slice($board_pins, 0, $max);
                            $bookmark = '-end-';
                            break;
                        }

                    }

                    $board_page++;
                    
                }catch (Exception $e) {
                    return $e;
                }
            }
            
            return $board_pins;
            
        }
        
        /**
         * 
         * @param array $board
         * @param type $bookmark Used to handle pagination.  If null, starts from the beginning.
         * @return array where 'pins' are the fetched pins and 'bookmark' is the token needed for pagination.
         * @throws PinterestPinnerException
         */

        private function getPageBoardPins($board, $bookmark = null){

            $page_pins = array();
            $user_resources = $this->getUserResources();
            $user_url = self::PINTEREST_URL . $user_resources['username']. '/';
            //$board_url = sprintf('/%1$s/%2$s/',$user_resources['username'],$board['slug']);
            $request_url = 'https://www.pinterest.com/resource/BoardFeedResource/get/';

            if (isset($user_resources['username']) && isset($board['id'])){
                
                $post_data_options = array(
                    'board_id'      => $board['id'],
                    //'board_url'     => $board['url'],
                    'board_layout'  => 'default',
                    'prepend'       => true,
                    'page_size'     => null,
                    'access'        => array('write','delete'),
                    'bookmarks'     => (array)$bookmark
                );

                if ($bookmark){ //used for pagination. Bookmark is defined when it is not the first page.
                    $post_data_options['data']['options']['bookmarks'] = $bookmark;
                }

                $post_data = array(
                    'data' => json_encode(array(
                        'options' => $post_data_options,
                        'context' => new stdClass,
                    )),
                    //'source_url' => $board_url,
                    'module_path' => sprintf('UserProfilePage(resource=UserResource(username=%1$s, invite_code=null))>UserProfileContent(resource=UserResource(username=%1$s, invite_code=null))>UserBoards()>Grid(resource=ProfileBoardsResource(username=%1$s))>GridItems(resource=ProfileBoardsResource(username=%1$s))>Board(show_board_context=false, show_user_icon=false, view_type=boardCoverImage, component_type=1, resource=BoardResource(board_id=%2$d))',
                            $user_resources['username'],
                            $board['id']
                    ),
                    '_' => time()*1000 //js timestamp
                );

                curl_setopt_array( $this->_curl, array(
                        CURLOPT_HTTPHEADER => array_merge( $this->_http_headers, array(
                                'X-NEW-APP: 1',
                                'X-APP-VERSION: ' . $this->_getAppVersion(),
                                'X-Requested-With: XMLHttpRequest',
                                'Accept: application/json, text/javascript, */*; q=0.01',
                                'X-CSRFToken: ' . $this->_getCSRFToken(),
                        ) ),
                        CURLOPT_URL        => $request_url,
                        CURLOPT_POSTFIELDS => $post_data,
                        CURLOPT_REFERER    => $user_url,
                        CURLOPT_HEADER      => false
                    ) );
                
                $this->_content = curl_exec( $this->_curl );   
                
                if (curl_getinfo($this->_curl, CURLINFO_HTTP_CODE) == 200) {

                    $response = json_decode($this->_content, true);

                    //pins
                    if (isset($response['resource_data_cache'][0]['data'])){
                        
                        $page_pins = $response['resource_data_cache'][0]['data'];

                        //remove items that have not the "pin" type (like module items)
                        $page_pins = array_filter(
                            $page_pins,
                            function ($e) {
                                return $e['type'] == 'pin';
                            }
                        );
                        
                    }
                    
                    //bookmark (pagination)
                    if (!isset($response['resource']['options']['bookmarks'][0])){
                        throw new PinterestPinnerException( 'getPageBoardPins(): Missing bookmark' );
                    }else{
                        $bookmark = $response['resource']['options']['bookmarks'][0];
                    }
                    
                    return array('pins'=>$page_pins,'bookmark'=>$bookmark);

                }
            }


            throw new PinterestPinnerException( 'Error getting user boards.' );
        }
        
        public function getUserBoards(){
            
            if ( !empty($this->user_boards) ) {
                    return $this->user_boards;
            }

            $user_resources = $this->getUserResources();
            $user_url = self::PINTEREST_URL . $user_resources['username']. '/';
            $boards_url = 'https://www.pinterest.com/resource/BoardPickerBoardsResource/get/';

            if (isset($user_resources['username'])){
   
                $post_data = array(
                    'data' => json_encode(array(
                        'options' => array(
                            'filter' => 'all',
                            'field_set_key' => 'board_picker',
                            'allow_stale' => true,
                        ),
                        'context' => new stdClass,
                    )),
                    'source_url' => '/'.$user_resources['username'].'/boards/',
                    'module_path' => sprintf('App()>UserProfilePage(resource=UserResource(username=%1$s))>UserInfoBar(tab=boards, spinner=[object Object], resource=UserResource(username=%1$s, invite_code=null))',
                            $user_resources['username']),
                    '_' => time()*1000 //js timestamp
                );


                curl_setopt_array( $this->_curl, array(
                        CURLOPT_HTTPHEADER => array_merge( $this->_http_headers, array(
                                'X-NEW-APP: 1',
                                'X-APP-VERSION: ' . $this->_getAppVersion(),
                                'X-Requested-With: XMLHttpRequest',
                                'Accept: application/json, text/javascript, */*; q=0.01',
                                'X-CSRFToken: ' . $this->_getCSRFToken(),
                        ) ),
                        CURLOPT_URL        => $boards_url,
                        CURLOPT_POSTFIELDS => $post_data,
                        CURLOPT_REFERER    => $user_url,
                        CURLOPT_HEADER      => false
                    ) );
                $this->_content = curl_exec( $this->_curl );                 
                if (curl_getinfo($this->_curl, CURLINFO_HTTP_CODE) == 200) {

                    $response = json_decode($this->_content, true);

                    if (isset($response['resource_data_cache'][0]['data']['all_boards'])){

                        $this->user_boards = $response['resource_data_cache'][0]['data']['all_boards'];
                        return $this->user_boards;
                    }

                }
            }


            throw new PinterestPinnerException( 'Error getting user boards.' );

        }

        public function getUserResources(){

            if ( !empty($this->user_resources) ) {
                    return $this->user_resources;
            }
            
            $this->_postLogin();

            curl_setopt_array( $this->_curl, array(
                    CURLOPT_HTTPHEADER => array_merge( $this->_http_headers, array(
                            'X-NEW-APP: 1',
                            'X-APP-VERSION: ' . $this->_getAppVersion(),
                            'X-Requested-With: XMLHttpRequest',
                            'Accept: application/json, text/javascript, */*; q=0.01',
                            'X-CSRFToken: ' . $this->_getCSRFToken(),
                    ) ),
                    CURLOPT_URL        => self::PINTEREST_URL . 'me',
                    //CURLOPT_POSTFIELDS => $post_data,
                    CURLOPT_REFERER    => self::PINTEREST_URL,
                    CURLOPT_HEADER      => false
            ) );
            $this->_content = curl_exec( $this->_curl );

            if (curl_getinfo($this->_curl, CURLINFO_HTTP_CODE) == 200) {
                $response = json_decode($this->_content, true);
                if (isset($response['resource_data_cache'][0]['data'])){

                    $user_resources = $response['resource_data_cache'][0]['data'];

                    $keep = array(
                        'last_name',
                        'following_count',
                        'like_count',
                        'full_name',
                        'first_name',
                        'secret_board_count',
                        'follower_count',
                        'board_count',
                        'username',
                        'pin_count'
                    );

                    foreach((array)$user_resources as $slug=>$value){
                        if (!in_array($slug,$keep)) continue;
                        $this->user_resource[$slug] = $value;
                    }

                    return $this->user_resource;
                }

            }

            throw new PinterestPinnerException( 'Error getting user resources.' );

        }

    }

}

if (!class_exists('PinterestPinnerException')) {

    class PinterestPinnerException extends Exception {
    }

}