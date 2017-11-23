<?php

include 'RemoteForm.php';

class Facebook extends Service {

    private $ch;
    private $debug = true;
    private $_currentDocument = null;

    /**
     * @var DOMXpath $_navigator An XPath for the current document
     */
    private $_navigator = null;

    /**
     * Function executed when the service is called
     *
     * @param Request $request
     * @return Response
     */
    public function _main(Request $request, $agent = 'default') {
        if (!file_exists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $request->email . '.cookie')) {
            $di = \Phalcon\DI\FactoryDefault::getDefault();
            $byEmail = $di->get('environment') != "app";
            $response = new Response();
            $response->setResponseSubject("Login en Facebook");
            $response->createFromTemplate("login.tpl", array());
            return $response;
        } else {

            $this->iniciar($request->email);
            $url = "https://m.facebook.com/";
            $this->navigate($url);
            $html = $this->getSource();
            //Verificar si es POST
            // Construir  POST
            // Preparing POST data
            $paramsbody = trim($request->body);
            $p = strpos($paramsbody, "\n");
            //Obtenet la cadena q contiene los parametros
            if ($p !== false)
                $paramsbody = substr($paramsbody, $p);
            if (strpos($paramsbody, '=') === false)
                $paramsbody = false;
            else
                $paramsbody = trim($paramsbody);
            if ($paramsbody !== false) {
                $post = $paramsbody;
                if ($post != '') {
                    $arr = explode("&", $post);
                    $post = array();
                    foreach ($arr as $v) {
                        $arr2 = explode('=', $v);
                        if (!isset($arr2[1]))
                            $arr2[1] = '';
                        $post[$arr2[0]] = $arr2[1];
                    }
                } else
                    $post = array();
                $this->navigatePOST($url, $post);
            }
            else {
                //eliminar parametros
                //$keys = array("refid","icm");
                //$url= $this->remove_url_query_args($url,$keys);
                $this->navigate($url);
            }

            // check if the response is for app or email
            $di = \Phalcon\DI\FactoryDefault::getDefault();
            $byEmail = $di->get('environment') != "app";
            $response = new Response();
            $response->setResponseSubject("Su web {$request->query}");
            $response->createFromTemplate("basic.tpl", array("body" => $html, "url" => $url, "byEmail" => $byEmail));
            return $response;
        }
    }

    public function _login(Request $request, $agent = 'default') {
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $byEmail = $di->get('environment') != "app";
        $response = new Response();
        $response->setResponseSubject("Login en Facebook");
        $response->createFromTemplate("login.tpl", array());
        return $response;
    }

    public function _insertarusuario(Request $request, $agent = 'default') {
        $parametros = $request->body;
        $direccion = $request->email;
        $this->iniciar($request->email);
        $split_complete = array();
        /// if ($parametros != '') {
        $split_parameters = explode('&', trim($parametros));
        for ($i = 0; $i < count($split_parameters); $i++) {
            $final_split = explode('=', $split_parameters[$i]);
            $split_complete[$i][0] = $final_split[1];
            $split_complete[$i][1] = $final_split[0];
        }


        $this->navigate("https://en-gb.facebook.com/login");

        try {
            $f = $this->getForm("//form[@id='login_form']");
            $f->setAttributeByName('email',  $split_complete[0][0]);
            $f->setAttributeByName('pass',$split_complete[1][0]);
            $ac = $f->getAction();
            $f->setAction("https://m.facebook.com" . $ac);
            $this->submitForm($f
                    , 'fulltext'
            )->click("login");
        } catch (Exception $r) {
            echo $r;
        }
        $html = $this->getSource();
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $byEmail = $di->get('environment') != "app";
        $response = new Response();
        $response->setResponseSubject("Su web {$request->query}");
        $response->createFromTemplate("basic.tpl", array("body" => $html, "url" => $url, "byEmail" => $byEmail));
        return $response;
    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////Funciones necesarias para el CURL////////////////////////////////////////////////// 
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function iniciar($cookie_name) {
        // initialize curl handle
        $this->ch = curl_init();
        //set various options
        //set error in case http return code bigger than 300
        curl_setopt($this->ch, CURLOPT_FAILONERROR, true);
        // use gzip if possible
        curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip, deflate');
        // do not veryfy ssl
        // this is important for windows
        // as well for being able to access pages with non valid cert
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . DIRECTORY_SEPARATOR . $cookie_name . '.cookie');
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . DIRECTORY_SEPARATOR . $cookie_name . '.cookie');
    }

    /**
     * Send post data to target URL	 
     * return data returned from url or false if error occured
     * @param string url
     * @param mixed post data (assoc array ie. $foo['post_var_name'] = $value or as string like var=val1&var2=val2)
     * @param string ip address to bind (default null)
     * @param int timeout in sec for complete curl operation (default 10)
     * @return string data
     * @access public
     */
    function send_post_data($url, $postdata, $ip = null, $timeout = 10) {
        //set various curl options first
        // set url to post to
        curl_setopt($this->ch, CURLOPT_URL, $url);
        // return into a variable rather than displaying it
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        //bind to specific ip address if it is sent trough arguments
        if ($ip) {
            if ($this->debug) {
                echo "Binding to ip $ip\n";
            }
            curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
        }
        //set curl function timeout to $timeout
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
        //set method to post
        curl_setopt($this->ch, CURLOPT_POST, true);
        // set post string
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postdata);
        //and finally send curl request
        $result = $this->curl_exec_redir($this->ch);
        if (curl_errno($this->ch)) {
            if ($this->debug) {
                echo "Error Occured in Curl\n";
                echo "Error number: " . curl_errno($this->ch) . "\n";
                echo "Error message: " . curl_error($this->ch) . "\n";
            }
            return false;
        } else {
            return $result;
        }
    }

    /**
     * fetch data from target URL	 
     * return data returned from url or false if error occured
     * @param string url	 
     * @param string ip address to bind (default null)
     * @param int timeout in sec for complete curl operation (default 5)
     * @return string data
     * @access public
     */
    function fetch_url($url, $ip = null, $timeout = 5) {
        // set url to post to
        curl_setopt($this->ch, CURLOPT_URL, $url);
        //set method to get
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        // return into a variable rather than displaying it
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        //bind to specific ip address if it is sent trough arguments
        if ($ip) {
            if ($this->debug) {
                echo "Binding to ip $ip\n";
            }
            curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
        }
        //set curl function timeout to $timeout
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
        //and finally send curl request
        $result = $this->curl_exec_redir($this->ch);
        if (curl_errno($this->ch)) {
            if ($this->debug) {
                echo "Error Occured in Curl\n";
                echo "Error number: " . curl_errno($this->ch) . "\n";
                echo "Error message: " . curl_error($this->ch) . "\n";
            }
            return false;
        } else {
            return $result;
        }
    }

    function curl_exec_redir($ch) {
        static $curl_loops = 0;
        static $curl_max_loops = 20;
        if ($curl_loops++ >= $curl_max_loops) {
            $curl_loops = 0;
            return FALSE;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        $data = curl_exec($ch);
        $data = str_replace("\r", "\n", str_replace("\r\n", "\n", $data));
        list($header, $data) = explode("\n\n", $data, 2);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //echo "*** Got HTTP code: $http_code ***\n";
        //echo "**  Got headers: \n$header\n\n";

        if ($http_code == 301 || $http_code == 302) {
            // If we're redirected, we should revert to GET
            curl_setopt($ch, CURLOPT_HTTPGET, true);

            $matches = array();
            preg_match('/Location:\s*(.*?)(\n|$)/i', $header, $matches);
            $url = @parse_url(trim($matches[1]));
            if (!$url) {
                //couldn't process the url to redirect to
                $curl_loops = 0;
                return $data;
            }
            $last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            if (empty($url['scheme']))
                $url['scheme'] = $last_url['scheme'];
            if (empty($url['host']))
                $url['host'] = $last_url['host'];
            if (empty($url['path']))
                $url['path'] = $last_url['path'];
            $new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . (!empty($url['query']) ? '?' . $url['query'] : '');
            //echo "Being redirected to $new_url\n";
            curl_setopt($ch, CURLOPT_URL, $new_url);
            return $this->curl_exec_redir($ch);
        } else {
            $curl_loops = 0;
            return $data;
        }
    }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////A partir de aqui maneja el arbol dom del HTML////////////////////////////////////////////////// 
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Updates the current document handlers based on the given data
     * @param HTML $data The fetched data
     * @param String $url The URL just loaded
     */
    private function _handleResponse($data, $url) {
        // We must have fetched a URL
        if (!$url) {
            throw new \Exception("Could not load url: " . $url);
        }

        // Attempt to parse the document
        $this->_currentDocument = new \DOMDocument();
        if (!( @$this->_currentDocument->loadHTML($data) )) {
            throw new \Exception("Malformed HTML server response from url: " . $url);
        }
        $links = $this->_currentDocument->getElementsByTagName('a');
        if ($links->length > 0) {
            foreach ($links as $link) {
                $href = $link->getAttribute('href');

                if ($href == false || empty($href))
                    $href = $link->getAttribute('data-src');

                if (substr($href, 0, 1) == '#') {
                    $link->setAttribute('href', '');
                    continue;
                }
                if (strtolower(substr($href, 0, 7)) == 'mailto:')
                    continue;
                //$this->formToLink( $this->_currentDocument);
                // $di = \Phalcon\DI\FactoryDefault::getDefault();
                // $wwwhttp = "http://localhost/apretaste/public/";
                // $d="$wwwhttp/run/display?subject=facebook " . $href ;
                //  $link->setAttribute('href',$d);
            }
        }
        $forms = $this->_currentDocument->getElementsByTagName('form');
        if ($forms->length > 0) {
//            foreach ($forms as $form) {
//                $a = $form->getAttribute('action');
//                $wwwhttp = "http://localhost/apretaste/public/";
//                $d = "$wwwhttp/run/display?subject=facebook " . $a;
//                $f = new RemoteForm($form);
//                $body = "&amp;body" . '?' . http_build_query($f->getParameters());
//                $form->setAttribute('action', $d . $body);
//            }
        }
        $this->_currentDocument->saveHTML();
        $this->_rawdata = $data;

        // Generte a XPath navigator
        $this->_navigator = new \DOMXpath($this->_currentDocument);
    }

    /**
     * Returns a form mapped through RemoteForm  matching the given XPath or element
     * @param The $formMatch form to utilize (XPath or DOMElement)
     * @return RemoteForm The matched form
     */
    public function getForm($formMatch) {
        if ($formMatch instanceof \DOMElement) {
            $form = $formMatch;
        } else if (is_string($formMatch)) {
            // Find the element
            $form = $this->_navigator->query($formMatch);

            // No element found
            if ($form->length != 1) {
                throw new \Exception($form->length . " forms found matching: " . $formMatch);
            }

            $form = $form->item(0);
        } else {
            throw new \Exception("Illegal expression given to getForm");
        }

        // New RemoteForm
        return new RemoteForm($form);
    }

    /**
     * Submits the given form.
     *
     * If $submitButtonName is given, that name is also submitted as a POST/GET value
     * This is available since some forms act differently based on which submit button
     * you press
     * @param RemoteForm $form The form to submit
     * @param String $submitButtonName The submit button to click
     * @return Browser Returns this browser object for chaining
     */
    public function submitForm(RemoteForm $form, $submitButtonName = '') {
        // Find the button, and set the given attribute if we're pressing a button
        if (!empty($submitButtonName)) {
            $button = $this->_navigator->query("//input[@type='submit'][@name='" . str_replace("'", "\'", $submitButtonName) . "']");
            if ($button->length === 1) {
                $form->setAttributeByName($submitButtonName, $button->item(0)->getAttribute('value'));
            }
        }

        // Handle get/post
        switch (strtolower($form->getMethod())) {
            case 'get':
                /**
                 * If we're dealing with GET, we build the query based on the
                 * parameters that RemoteForm finds, and then navigate to
                 * that URL
                 */
                $questionAt = strpos($form->getAction(), '?');
                if ($questionAt === false) {
                    $questionAt = strlen($form->getAction());
                }
                $url = substr($form->getAction(), 0, $questionAt);
                $url = $this->_resolveUrl($url);
                $url .= '?' . http_build_query($form->getParameters());
                $this->navigate($url);
                break;
            case 'post':
                /**
                 * If we're posting, we simply build a query string, and
                 * pass that as the post data to the Curl HTTP client's
                 * post handler method. Then we handle the response.
                 */
                $this->_handleResponse($this->send_post_data($form->getAction(), $form->getParameters()), $form->getAction());
                break;
        }

        // Chain
        return $this;
    }

    /**
     * Returns the source of the current page
     * @return String The current HTML
     */
    public function getSource() {
        return $this->_currentDocument->saveHTML();
    }

    /**
     * Navigates to the given URL
     * @param String $url The url to navigate to, may be relative
     * @return Browser Returns this browser object for chaining
     */
    public function navigate($url) {
        /**
         * Resolve the URL
         */
        // $url = $this -> _resolveUrl ( $url );

        /**
         * After resolving, it must be absolute, otherwise we're stuck...
         */
        //   if ( !strpos ( $url, 'http' ) === 0 ) {
        //      throw new \Exception ( "Unknown protocol used in navigation url: " . $url );
        //  }

        /**
         * Finally, fetch the URL, and handle the response
         */
        $this->_handleResponse($this->fetch_url($url), $url);

        /**
         * And make us chainable
         */
        return $this;
    }

    /**
     * Navigates to the given URL
     * @param String $url The url to navigate to, may be relative
     * @return Browser Returns this browser object for chaining
     */
    public function navigatePOST($url, $post) {
        /**
         * After resolving, it must be absolute, otherwise we're stuck...
         */
        if (!strpos($url, 'http') === 0) {
            throw new \Exception("Unknown protocol used in navigation url: " . $url);
        }

        /**
         * Finally, fetch the URL, and handle the response
         */
        $this->_handleResponse($this->send_post_data($url, $post), $url);

        /**
         * And make us chainable
         */
        return $this;
    }

    /**
     * Emulates a click on the given link.
     *
     * The link may be given either as an XPath query, or as plain text, in which case
     * this method will first search for any link or submit button with the exact text
     * given, and then attempt to find one that contains it.
     * @param String $link XPath or link/submit-button title
     * @return Browser Returns this browser object for chaining
     */
    public function click($link) {
        // Attempt direct query
        $a = @$this->_navigator->query($link);
        if (!$a || $a->length != 1) {
            // Attempt exact title match
            $link_as_xpath = "//a[text() = '" . str_replace("'", "\'", $link) . "'] | //input[@type = 'submit'][@value = '" . str_replace("'", "\'", $link) . "']";
            $a = @$this->_navigator->query($link_as_xpath);

            if (!$a) {
                // This would mean the initial $link was an XPath expression
                // Redo it without error suppression
                $this->_navigator->query($link);
                throw new \Exception("Failed to find matches for selector: " . $link);
            }

            if ($a->length != 1) {
                // Attempt title contains match
                $link_as_xpath_contains = "//a[contains(.,'" . str_replace("'", "\'", $link) . "')]";
                $a = $this->_navigator->query($link_as_xpath_contains);

                // Still no match, throw error
                if ($a->length != 1) {
                    throw new \Exception(intval($a->length) . " links found matching: " . $link);
                }

                $link_as_xpath = $link_as_xpath_contains;
            }
            $link = $link_as_xpath;
        }

        // Fetch the element
        $a = $a->item(0);

        /**
         * If we've found a submit button, we find the parent form and submit it
         */
        if (strtolower($a->tagName) === 'input' && strtolower($a->getAttribute('type')) === 'submit') {
            $form = $a;
            while (strtolower($form->tagName !== 'form')) {
                $form = $form->parentNode;
            }

            if (strtolower($form->tagName) !== 'form') {
                throw new \Exception("Button " . $link . " exists, but does not belong to a form");
            }

            $this->submitForm($this->getForm($form), $a->getAttribute('name'));
            // Chain
            return $this;
        }

        /**
         * Otherwise, we simply navigate by the links href
         */
        $this->navigate($this->_resolveUrl($a->getAttribute('href')));

        // Chain
        return $this;
    }

}
