<?php

include 'RemoteForm.php';

class Facebook extends Service {

	private $_ch;
	private $_document = null;
	private $_navigator = null;

	/**
	 * Function executed when the service is called
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function _main(Request $request, $agent = 'default')
	{
		if (!file_exists($this->utils->getTempDir() . $request->email . '.cookie')) {
			$response = new Response();
			$response->setResponseSubject("Login en Facebook");
			$response->createFromTemplate("login.tpl", []);
			return $response;
		} else {
			$this->iniciar($request->email);
			$argument = $request->query;

			$pos1 = strpos($argument, "{");
			$sub = substr($argument, $pos1); //eliminar la url
			$pos2 = strpos($sub, "}");
			$url = trim(substr($argument, 0, $pos1)); //url

			if (strpos(urldecode($url), "https://upload.facebook.com") !== false) {
				$url = urldecode($url);
			} else {
				$url = "https://m.facebook.com" . urldecode($url);
			}

			if (!$pos1) {
				if (strpos(urldecode($url), "https://upload.facebook.com") !== false) {
					$url = urldecode($request->query);
				} else {

					$url = "https://m.facebook.com" . urldecode($request->query);
				}
			}
			$para = (array) json_decode(trim(substr($sub, 0, $pos2 + 1))); //parametros
			$pos3 = strpos($argument, "}");
			$parametros = substr($argument, $pos3 + 1); //resto
			foreach ($para as $p => $s) {
				if ($s == "_parametro_") {
					$para[$p] = $parametros;
				}
			}
			$url = str_replace("&amp;", "&", $url);
			if (count($para) != 0)
				$this->navigatePOST($url, $para);
			else
				$this->navigate($url);

			$html = $this->getSource();
			$arrayIma = $this->saveImg($html);
			$html = $this->getSource();

			// clean HTML
			$html = str_replace("&Acirc;", "", $html);
			$html = str_replace("&acirc;", "", $html);
			$html = str_replace("&#128;", "", $html);
			$html = str_replace("&#142;", "", $html);
			$html = str_replace("&Atilde;&iexcl;", "&aacute;", $html);
			$html = str_replace("&Atilde;&copy;", "&eacute;", $html);
			$html = str_replace("&Atilde;&shy;", "&iacute;", $html);
			$html = str_replace("&Atilde;&sup3;", "&oacute;", $html);
//			$html = str_replace("UUU", "&uacute;", $html);
			$html = str_replace("&Atilde;&plusmn;", "&ntilde;", $html);

			// send data to the view
			$response = new Response();
			$response->setResponseSubject("Facebook ");
			$response->createFromTemplate("basic.tpl", ["body" => $html, "url" => $url], $arrayIma);
			return $response;
		}
	}

	/**
	 * salir
	 */
	public function _salir(Request $request, $agent = 'default')
	{
		if (file_exists($this->utils->getTempDir() . $request->email . '.cookie')) {
			unlink($this->utils->getTempDir() . $request->email . '.cookie');
		}

		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$byEmail = $di->get('environment') != "app";
		$response = new Response();
		$response->setResponseSubject("Login en Facebook");
		$response->createFromTemplate("login.tpl", array());
		return $response;
	}

	/**
	 * login
	 */
	public function _login(Request $request, $agent = 'default')
	{
		$response = new Response();
		$response->setResponseSubject("Login en Facebook");
		$response->createFromTemplate("login.tpl", array());
		return $response;
	}

	/**
	 * insertar usuario
	 */
	public function _pagina(Request $request, $agent = 'default')
	{
		$direccion = $request->email;
		$this->iniciar($request->email);
		$argument = explode(" ", $request->query);

		$this->navigateLogin("https://en-gb.facebook.com/login");

		try {
			$f = $this->getForm("//form[@id='login_form']");
			$f->setAttributeByName('email', $argument[0]);
			$f->setAttributeByName('pass', $argument[1]);
			$ac = $f->getAction();
			$f->setAction("https://m.facebook.com" . $ac);
			$this->submitForm($f, 'fulltext')->click("login");
		} catch (Exception $r) {}

		// get HTML page
		$this->navigate("https://m.facebook.com/");
		$html = $this->getSource();

		// send info to the view
		$response = new Response();
		$response->setResponseSubject("Su web {$request->query}");
		$response->createFromTemplate("basic.tpl", ["body"=>$html]);
		return $response;
	}

///////////////////////////////////////////////////////////////////////////////
///////////////////////////Funciones necesarias para el CURL///////////////////
///////////////////////////////////////////////////////////////////////////////

	/**
	 * iniciar
	 */
	public function iniciar($cookie_name)
	{
		$this->_ch = curl_init();
		curl_setopt($this->_ch, CURLOPT_FAILONERROR, true);
		curl_setopt($this->_ch, CURLOPT_ENCODING, "UTF-8");
		curl_setopt($this->_ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->_ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:50.0) Gecko/20100101 Firefox/50.0");
		 curl_setopt($this->_ch, CURLOPT_COOKIEJAR,$this->utils->getTempDir() . $cookie_name . '.cookie');
		curl_setopt($this->_ch, CURLOPT_COOKIEFILE, $this->utils->getTempDir() . $cookie_name . '.cookie');
	}

	/**
	 * Send post data to target URL
	 * return data returned from url or false if error occured
	 *
	 * @param string url
	 * @param mixed post data (assoc array ie. $foo['post_var_name'] = $value or as string like var=val1&var2=val2)
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 10)
	 * @return string data
	 * @access public
	 */
	function send_post_data($url, $postdata, $ip = null, $timeout = 10)
	{
		curl_setopt($this->_ch, CURLOPT_URL, $url);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
		if ($ip) curl_setopt($this->_ch, CURLOPT_INTERFACE, $ip);
		curl_setopt($this->_ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($this->_ch, CURLOPT_POST, true);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $postdata);

		$result = $this->curl_exec_redir($this->_ch);
		if (curl_errno($this->_ch)) return false;
		else return $result;
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
	function fetch_url($url, $ip = null, $timeout = 5)
	{
		curl_setopt($this->_ch, CURLOPT_URL, $url);
		curl_setopt($this->_ch, CURLOPT_HTTPGET, true);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
		if ($ip)
			curl_setopt($this->_ch, CURLOPT_INTERFACE, $ip);
		curl_setopt($this->_ch, CURLOPT_TIMEOUT, $timeout);

		$result = $this->curl_exec_redir($this->_ch);
		if (curl_errno($this->_ch))
			return false;
		else
			return $result;
	}

	/**
	 * curl exec redir
	 */
	function curl_exec_redir($ch)
	{
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

			//echo "Being redirected to $new_url
			curl_setopt($ch, CURLOPT_URL, $new_url);
			return $this->curl_exec_redir($ch);
		} else {
			$curl_loops = 0;
			return $data;
		}
	}

//////////////////////////////////////////////////////////////////////////////
////////////A partir de aqui maneja el arbol dom del HTML/////////////////////
//////////////////////////////////////////////////////////////////////////////

	/**
	 * Updates the current document handlers based on the given data
	 * @param HTML $data The fetched data
	 * @param String $url The URL just loaded
	 */
	private function _handleResponse($data, $url)
	{
		// We must have fetched a URL
		if (!$url)
			throw new \Exception("Could not load url: " . $url);

		// Attempt to parse the document
		$this->_document = new \DOMDocument('1.0', 'UTF-8');
		if (!( @$this->_document->loadHTML($data) )) {
			throw new \Exception("Malformed HTML server response from url: " . $url);
		}

		// include links plugin
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		include_once "$wwwroot/app/plugins/function.link.php";

		$result = "";
		preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $this->_document->saveHTML(), $result);
		$links = $this->_document->getElementsByTagName('a');
		if (!empty($result)) {
			// Found a link.
			for ($i = 0; $i < count($result['href']); $i++) {
				$href = $result['href'][$i];
				$href = urlencode($href);

				$params = ["href" => "FACEBOOK $href", "caption" => "Click"];
				$aplink = smarty_function_link($params, null);
				$href = php::substring($aplink, "href='", "' onclick");
				$onclick = php::substring($aplink, "onclick='", "' >");
				$links->item($i)->setAttribute('href', $href);
				$links->item($i)->setAttribute('onclick', $onclick);
			}
		}

		$forms = $this->_document->getElementsByTagName('form');

		if ($forms->length > 0) {
			foreach ($forms as $form) {
				$action = $form->getAttribute('action');
				$f = new RemoteForm($form);
				$inputs = $form->getElementsByTagName('input');
				foreach ($inputs as $input) {
					if ($input->getAttribute('type') == "submit") {
						$inputValue = $input->getAttribute('value');
						$parametros = $f->getParameters();
						$asuntoA = array();
						$generearInput = "";
						$hidenInput = $this->getHidenInput($form);

						foreach ($parametros as $parametro => $value) {
							if (isset($hidenInput[$parametro])) {
								// echo $parametro;
							}

							if ($value != "" || isset($hidenInput[$parametro])) {
								$asuntoA[$parametro] = $value;
							}
							if ($value == "" && !isset($hidenInput[$parametro])) {
								$asuntoA[$parametro] = "_parametro_";
								$generearInput = $generearInput . $parametro;
							}
						}
						$asuntoA[$input->getAttribute('name')] = $input->getAttribute('value');
						$asunto = json_encode($asuntoA);
						$asunto = urlencode($action) . "  " . $asunto;
						$btn = $this->_document->createElement("a");  // Create a <button> element
						$href = $this->_document->createAttribute("href");
						$btn->appendChild($href);
						$t = $this->_document->createTextNode($input->getAttribute('value'));
						$btn->appendChild($t);

						$send = "apretaste.doaction('FACEBOOK " . $asunto . "', true, '" . "Escribir el Texto " . "', true); return false;";
						$btn->setAttribute('onclick', $send);
						$class = $input->getAttribute('class');
						$btn->setAttribute('class', $class);
						$input->parentNode->appendChild($btn);
						$input->parentNode->removeChild($input);
					}
				}
			}
		}

		// remove all comments
		$xpath = new DOMXPath($this->_document);
		foreach ($xpath->query('//comment()') as $comment) {
			$comment->parentNode->removeChild($comment);
		}

		while (($r = $this->_document->getElementsByTagName("script")) && $r->length) {
			$r->item(0)->parentNode->removeChild($r->item(0));
		}

		// remove outside css
		while (($r = $this->_document->getElementsByTagName("link")) && $r->length) {
			$r->item(0)->parentNode->removeChild($r->item(0));
		}

		while (($r = $this->_document->getElementsByTagName("iframe")) && $r->length) {
			$r->item(0)->parentNode->removeChild($r->item(0));
		}

		// remove <meta> tags
		while (($r = $this->_document->getElementsByTagName("meta")) && $r->length) {
			$r->item(0)->parentNode->removeChild($r->item(0));
		}

		while (($r = $this->_document->getElementsByTagName("input")) && $r->length) {
			$r->item(0)->parentNode->removeChild($r->item(0));
		}

		while (($r = $this->_document->getElementsByTagName("select")) && $r->length) {
			$r->item(0)->parentNode->removeChild($r->item(0));
		}

		while (($r = $this->_document->getElementsByTagName("textarea")) && $r->length) {
			$r->item(0)->parentNode->removeChild($r->item(0));
		}

		$body = $xpath->query('//body')->item(0);
		$this->_document->saveXml($body);

		while (($r = $this->_document->getElementsByTagName("")) && $r->length) {
			$r->item(0)->parentNode->removeAttribute('class');
		}

		$this->_document->saveHTML();
		$this->_rawdata = $data;

		// Generte a XPath navigator
		$this->_navigator = new \DOMXpath($this->_document);
	}

	/**
	 * get hidden input
	 */
	private function getHidenInput($form)
	{
		$hiden = array();
		$inputsHiden = $form->getElementsByTagName('input');
		foreach ($inputsHiden as $i) {
			if ($i->getAttribute('type') == "hidden" && $i->getAttribute('value') == "") {
				$hiden[$i->getAttribute('name')] = " ";
			}
		}
		return $hiden;
	}

	/**
	 * handle response
	 */
	private function _handleResponseLogin($data, $url)
	{
		// We must have fetched a URL
		if (!$url) {
			throw new \Exception("Could not load url: " . $url);
		}

		$this->_document = new \DOMDocument();
		if (!( @$this->_document->loadHTML($data) )) {
			throw new \Exception("Malformed HTML server response from url: " . $url);
		}

		$this->_document->saveHTML();
		$this->_rawdata = $data;
		$this->_navigator = new \DOMXpath($this->_document);
	}

	/**
	 * Returns a form mapped through RemoteForm  matching the given XPath or element
	 * @param The $formMatch form to utilize (XPath or DOMElement)
	 * @return RemoteForm The matched form
	 */
	public function getForm($formMatch)
	{
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
	public function submitForm(RemoteForm $form, $submitButtonName = '')
	{
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
				// If we're dealing with GET, we build the query based on the
				// parameters that RemoteForm finds, and then navigate to that URL
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
				// If we're posting, we simply build a query string, and
				// pass that as the post data to the Curl HTTP client's
				// post handler method. Then we handle the response.
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
	public function getSource()
	{
		return $this->_document->saveHTML();
	}

	/**
	 * Navigates to the given URL
	 * @param String $url The url to navigate to, may be relative
	 * @return Browser Returns this browser object for chaining
	 */
	public function navigate($url)
	{
		$this->_handleResponse($this->fetch_url($url), $url);
		return $this;
	}

	/**
	 * navigate login
	 */
	public function navigateLogin($url)
	{
		$this->_handleResponseLogin($this->fetch_url($url), $url);
		return $this;
	}

	/**
	 * Navigates to the given URL
	 * @param String $url The url to navigate to, may be relative
	 * @return Browser Returns this browser object for chaining
	 */
	public function navigatePOST($url, $post)
	{
		// After resolving, it must be absolute, otherwise we're stuck...
		if (!strpos($url, 'http') === 0) {
			throw new \Exception("Unknown protocol used in navigation url: " . $url);
		}

		// Finally, fetch the URL, and handle the response
		$this->_handleResponse($this->send_post_data($url, $post), $url);

		// And make us chainable
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
	public function click($link)
	{
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

		// If we've found a submit button, we find the parent form and submit it
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

		/**
		 * Otherwise, we simply navigate by the links href
		 */
		$this->navigate($this->_resolveUrl($a->getAttribute('href')));
		return $this;
	}

/////////////////////////////////////////////////////////////////////////
//////////////////////Imagenes///////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////

	/**
	 * use file_put_contents() save image directively
	 * @param Integer $page
	 */
	function saveImg($html)
	{
		// Attempt to parse the document
		$arrayImagenes = array();

		$temp = $this->utils->getTempDir();
		$urlList = $this->getImgUrl($html);
		$this->_document = new \DOMDocument();
		if (!( @$this->_document->loadHTML($html) )) {
			throw new \Exception("Malformed HTML server response from url: " . $url);
		}

		// include image plugin
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		include "$wwwroot/app/plugins/function.img.php";

		$i = 0;
		$links = $this->_document->getElementsByTagName('img');
		foreach ($urlList as $url) {
			// get image path
			$name = $this->utils->generateRandomHash();
			$ext = substr(pathinfo($url, PATHINFO_EXTENSION), 0, 3);
			$file = "$temp$name.$ext";
			$url = trim(str_replace("&amp;", "&", $url));

			// download the image
			$this->download($url, $file);

			// add to the array of images for the view
			$arrayImagenes[$i] = $file;

			// get the src for the image into the template
			$img = smarty_function_img(["src" => $file], null);
			$src = php::substring($img, "src='", "' alt");
			$links->item($i)->setAttribute('src', $src);
			++$i;
		}

		$this->_document->saveHTML();
		$this->_rawdata = $html;
		//print_r($arrayImagenes);
		// Generte a XPath navigator
		$this->_navigator = new \DOMXpath($this->_document);
		return $arrayImagenes;
	}

	/**
	 * get images' url from a html file
	 * @param String $html
	 * @return Array
	 */
	function getImgUrl($html)
	{
		$pattern = '/<img src="(.*)" .*\/>/U';
		preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"][^\/>]*>/Ui', $html, $matches);
		return $matches[1];
	}

	/**
	 * use curl get file
	 * @param String $url
	 * @return Recourse
	 */
	function curlGet($url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 1024); // default expire time is 120s

		$result = curl_exec($curl);
		$info = curl_getinfo($curl);
		if ($info['http_code'] == 301)
			if (isset($info['redirect_url']) && $info['redirect_url'] != $url)
				return $this->curlGet($info['redirect_url']);
		curl_close($curl);

		return $result;
	}

	/**
	 * Downdload
	 */
	function download($url, $file)
	{
		$file = fopen($file, "w");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 0);
		curl_setopt($ch, CURLOPT_FILE, $file);
		curl_exec($ch);
		curl_close($ch);
		fclose($file);
	}

}
