<?php

require_once('RemoteForm.php');

class Browser {

    private $_curl = null;
    private $_currentDocument = null;
    private $_navigator = null;
    private $_rawdata = null;

    public function __construct($userAgent = '', $url = '') {

        $this->_curl = new \Curl_HTTP_Client(true);
        if (!empty($userAgent)) {
            $this->_curl->set_user_agent($userAgent);
        }
        if (trim($url) != '') {
            $this->navigate($url);
        }
    }

    public function navigate($url) {

        $this->_handleResponse($this->_curl->fetch_url($url), $url);

        return $this;
    }

    public function navigatePOST($url, $post) {

        $this->_handleResponse($this->_curl->send_post_data($url, $post), $url);

        return $this;
    }

    public function click($link) {
        $a = @$this->_navigator->query($link);
        if (!$a || $a->length != 1) {
            $link_as_xpath = "//a[text() = '" . str_replace("'", "\'", $link) . "'] | //input[@type = 'submit'][@value = '" . str_replace("'", "\'", $link) . "']";
            $a = @$this->_navigator->query($link_as_xpath);

            if (!$a) {
                $this->_navigator->query($link);
                throw new \Exception("Failed to find matches for selector: " . $link);
            }

            if ($a->length != 1) {
                $link_as_xpath_contains = "//a[contains(.,'" . str_replace("'", "\'", $link) . "')]";
                $a = $this->_navigator->query($link_as_xpath_contains);
                if ($a->length != 1) {
                    throw new \Exception(intval($a->length) . " links found matching: " . $link);
                }

                $link_as_xpath = $link_as_xpath_contains;
            }
            $link = $link_as_xpath;
        } $a = $a->item(0);


        if (strtolower($a->tagName) === 'input' && strtolower($a->getAttribute('type')) === 'submit') {
            $form = $a;
            while (strtolower($form->tagName !== 'form')) {
                $form = $form->parentNode;
            }

            if (strtolower($form->tagName) !== 'form') {
                throw new \Exception("Button " . $link . " exists, but does not belong to a form");
            }
            $this->submitForm($this->getForm($form), $a->getAttribute('name'));
            return $this;
        }


        $this->navigate($this->_resolveUrl($a->getAttribute('href')));

        return $this;
    }

    public function download($match, $filename) {
        $e = $this->_navigator->query($match);
        if (!$e || $e->length != 1) {
            throw new \Exception(intval($e->length) . " elements found matching: " . $match);
        }

        $e = $e->item(0);
        if (!$e->hasAttribute('src') && !$e->hasAttribute('href')) {
            throw new \Exception("No downloadable attribute found for element matching: " . $match);
        }
        $url = $this->_resolveUrl($e->hasAttribute('src') ? $e->getAttribute('src') : $e->getAttribute('href'));

        // Open a file handle, and download the file
        $fh = fopen($filename, 'w');
        $this->_curl->fetch_into_file($url, $fh);
        fclose($fh);
        return $this;
    }

    private function _handleResponse($data, $url) {
        if (!$url) {
            throw new \Exception("Could not load url: " . $url);
        }
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
            }
        }
        $forms = $this->_currentDocument->getElementsByTagName('form');

        $this->_currentDocument->saveHTML();
        $this->_rawdata = $data;


        $this->_navigator = new \DOMXpath($this->_currentDocument);
    }

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

        return new RemoteForm($form);
    }

    private function _resolveUrl($url) {
        $url = trim($url);
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        if ($url === '') {
            return $this->_curl->get_effective_url();
        }
        if ($url[0] === '/') {
            $port = ':' . parse_url($this->_curl->get_effective_url(), PHP_URL_PORT);
            return parse_url($this->_curl->get_effective_url(), PHP_URL_SCHEME) . '://' . parse_url($this->_curl->get_effective_url(), PHP_URL_HOST) . ( $port !== ':' ? $port : '' ) . $url;
        }

        $base = dirname($this->_curl->get_effective_url());
        $baseTag = $this->_navigator->query("//base[@href][last()]");
        if ($baseTag->length > 0) {
            $base = $baseTag->item(0)->getAttribute('href');
        }
        return $base . '/' . $url;
    }

    public function submitForm(RemoteForm $form, $submitButtonName = '') {
        if (!empty($submitButtonName)) {
            $button = $this->_navigator->query("//input[@type='submit'][@name='" . str_replace("'", "\'", $submitButtonName) . "']");
            if ($button->length === 1) {
                $form->setAttributeByName($submitButtonName, $button->item(0)->getAttribute('value'));
            }
        }

        switch (strtolower($form->getMethod())) {
            case 'get':
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
                $this->_handleResponse($this->_curl->send_post_data($this->_resolveUrl($form->getAction()), $form->getParameters()), $form->getAction());
                break;
        }

        // Chain
        return $this;
    }

    public function getSource() {
        return $this->_currentDocument->saveHTML();
    }

    public function getRawResponse() {
        return $this->_rawdata;
    }

    function castToDOMElement(\DOMNode $node) {
        if ($node instanceof DOMElement) {
            return $node;
        }
        return unserialize(preg_replace('/DOMNode/', 'DOMElement', serialize($node)));
    }

    public function parsearForm($data) {
        $this->_currentDocument = new \DOMDocument();
        if (!( @$this->_currentDocument->loadHTML($data) )) {
            throw new \Exception("Malformed HTML server response from url: " . $url);
        }
        $forms = $this->_currentDocument->getElementsByTagName('form');
        if ($forms->length > 0) {
            foreach ($forms as $form) {
                $a = $form->getAttribute('action');
                $wwwhttp = $di->get('path')['http'];
                $d = $wwwhttp . "/run/display?subject=facebook " . $a;
                $f = new RemoteForm($form);
                // $body = "&amp;body=" . '?' . http_build_query($f->getParameters())."";
                $form->setAttribute('action', 'mailto:' . "david.montero@uo.edu.cu" . '?subject=facebook ' . $a . ";body=");
                $form->setAttribute('method', 'post');
            }
        }

        $this->_currentDocument->saveHTML($data);
    }

    public function convertToMailTo($url, $body = '') {
        if (trim($href) == '')
            return '';

        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $fullhref = $url;
        $newhref = 'mailto:' . "david.montero@uo.edu.cu" . '?subject=facebook ' . $fullhref . ";body=" . $body;
        $newhref = str_replace("//", "/", $newhref);
        $newhref = str_replace("//", "/", $newhref);
        $newhref = str_replace("//", "/", $newhref);
        $newhref = str_replace("http:/", "http://", $newhref);
        $newhref = str_replace("http/", "http://", $newhref);

        return $newhref;
    }

}
