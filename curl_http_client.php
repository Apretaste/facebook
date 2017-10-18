<?php

class Curl_HTTP_Client {

    var $ch;
    var $debug = false;
    var $error_msg;

    function Curl_HTTP_Client($debug = false) {
        $this->debug = $debug;
        $this->init();
    }

    function init() {

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_FAILONERROR, true);

        curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
    }

    function set_credentials($username, $password) {
        curl_setopt($this->ch, CURLOPT_USERPWD, "$username:$password");
    }

    function set_referrer($referrer_url) {
        curl_setopt($this->ch, CURLOPT_REFERER, $referrer_url);
    }

    function set_user_agent($useragent) {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $useragent);
    }

    function include_response_headers($value) {
        curl_setopt($this->ch, CURLOPT_HEADER, $value);
    }

    function set_proxy($proxy) {
        curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
    }

    function send_post_data($url, $postdata, $ip = null, $timeout = 10) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        if ($ip) {
            if ($this->debug) {
                echo "Binding to ip $ip\n";
            }
            curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
        }
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postdata);
        $result = curl_exec_redir($this->ch);

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

    function fetch_url($url, $ip = null, $timeout = 5) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        if ($ip) {
            if ($this->debug) {
                echo "Binding to ip $ip\n";
            }
            curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
        }
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
        $result = curl_exec_redir($this->ch);

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

    function fetch_into_file($url, $fp, $ip = null, $timeout = 5) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        curl_setopt($this->ch, CURLOPT_FILE, $fp);
        if ($ip) {
            if ($this->debug) {
                echo "Binding to ip $ip\n";
            }
            curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
        }
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
        $result = curl_exec_redir($this->ch);

        if (curl_errno($this->ch)) {
            if ($this->debug) {
                echo "Error Occured in Curl\n";
                echo "Error number: " . curl_errno($this->ch) . "\n";
                echo "Error message: " . curl_error($this->ch) . "\n";
            }

            return false;
        } else {
            return true;
        }
    }

    function send_multipart_post_data($url, $postdata, $file_field_array = array(), $ip = null, $timeout = 30) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        if ($ip) {
            if ($this->debug) {
                echo "Binding to ip $ip\n";
            }
            curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
        }
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($this->ch, CURLOPT_POST, true);
        $headers = array("Expect: ");
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        $result_post = array();
        $post_array = array();
        $post_string_array = array();
        if (!is_array($postdata)) {
            return false;
        }

        foreach ($postdata as $key => $value) {
            $post_array[$key] = $value;
            $post_string_array[] = urlencode($key) . "=" . urlencode($value);
        }

        $post_string = implode("&", $post_string_array);


        if ($this->debug) {
            echo "Post String: $post_string\n";
        }
        if (!empty($file_field_array)) {
            foreach ($file_field_array as $var_name => $var_value) {
                if (strpos(PHP_OS, "WIN") !== false)
                    $var_value = str_replace("/", "\\", $var_value); // win hack
                $file_field_array[$var_name] = "@" . $var_value;
            }
        }
        $result_post = array_merge($post_array, $file_field_array);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $result_post);
        $result = curl_exec_redir($this->ch);

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

    function store_cookies($cookie_file) {

        curl_setopt($this->ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookie_file);
    }

    function set_cookie($cookie) {
        curl_setopt($this->ch, CURLOPT_COOKIE, $cookie);
    }

    function get_effective_url() {
        return curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
    }

    function get_http_response_code() {
        return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    }

    function get_error_msg() {
        $err = "Error number: " . curl_errno($this->ch) . "\n";
        $err .= "Error message: " . curl_error($this->ch) . "\n";

        return $err;
    }

    function close() {
        curl_close($this->ch);
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
        curl_setopt($ch, CURLOPT_URL, $new_url);
        return curl_exec_redir($ch);
    } else {
        $curl_loops = 0;
        return $data;
    }
}
