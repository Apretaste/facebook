<?php

require_once('Browser.php');
require_once('curl_http_client.php');

class Facebook extends Service {

    /**
     * Function executed when the service is called
     *
     * @param Request $request
     * @return Response
     */
    public function _main(Request $request, $agent = 'default') {
        $b = new Browser("Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
        // $this->loginfacebook($b);
        $post = "";
        $url = $request->query;
        $direccion = $request->email;

        $result = $this->existeUsuario($direccion);
        if (isset($result[0])) {
            //Login
            $b->navigate('https://m.facebook.com/');
            try {
                $f = $b->getForm("//form[@id='login_form']");
                $f->setAttributeByName('email', $result[0]->user);
                $f->setAttributeByName('pass', $result[0]->pass);
                $ac = $f->getAction();
                $f->setAction("https://m.facebook.com/" . $ac);
                $b->submitForm($f, 'fulltext')->click("login");
            } catch (Exception $r) {
                $b->navigate('https://m.facebook.com/');
                // //  $response = new Response();
                //   $response->setResponseSubject("Login {$request->query}");
                //   $response->createFromTemplate("login.tpl", array());
                //   return $response;
            }
        } else {
            //renderizar la vista del login
            $response = new Response();
            $response->setResponseSubject("Login {$request->query}");
            $response->createFromTemplate("login.tpl", array());
            return $response;
        }

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
            $b->navigatePOST("https://m.facebook.com" . $url, $post);
        }
        else {
            //eliminar parametros
            //$keys = array("refid","icm");
            //$url= $this->remove_url_query_args($url,$keys);
            $b->navigate("https://m.facebook.com" . $url);
        }

        $html = $b->getSource();
        $b->parsearForm($html);
        $html = $b->getSource();
        preg_match_all('/href="(.*?)"/', $html, $matches);
        for ($i = 0; $i < count($matches[0]); $i++) {
            $html = str_replace($matches[0][$i], " href=\"" . 'mailto:' . "david.montero@uo.edu.cu" . '?subject=facebook ' . $matches[1][$i] . ";body=\"", $html);
        }
        $html = $html . "https://m.facebook.com" . $url;
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $byEmail = $di->get('environment') != "app";
        $response = new Response();
        $response->setResponseSubject("Su web {$request->query}");
        $response->createFromTemplate("basic.tpl", array("body" => $html, "url" => $url, "byEmail" => $byEmail));
        return $response;
    }

    /* public function remove_url_query_args($url,$keys=array()) {
      $url_parts = parse_url($url);
      if(empty($url_parts['query'])) return $url;

      parse_str($url_parts['query'], $result_array);
      foreach ( $keys as $key ) { unset($result_array[$key]); }
      $url_parts['query'] = http_build_query($result_array);
      $url = (isset($url_parts["scheme"])?$url_parts["scheme"]."://":"").
      (isset($url_parts["user"])?$url_parts["user"].":":"").
      (isset($url_parts["pass"])?$url_parts["pass"]."@":"").
      (isset($url_parts["host"])?$url_parts["host"]:"").
      (isset($url_parts["port"])?":".$url_parts["port"]:"").
      (isset($url_parts["path"])?$url_parts["path"]:"").
      (isset($url_parts["query"])?"?".$url_parts["query"]:"").
      (isset($url_parts["fragment"])?"#".$url_parts["fragment"]:"");
      return $url;
      } */

    /*
      convertir for a get
      $url .= '?' . http_build_query ( $form -> getParameters() );

     */

    public function _login(Request $request, $agent = 'default') {
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $byEmail = $di->get('environment') != "app";
        $response = new Response();
        $response->setResponseSubject("Login en Facebook");
        $response->createFromTemplate("login.tpl", array());
        return $response;
    }

    /* Se loguea en facebook */

    private function loginfacebook(Browser $b, $user, $pass) {
        $b->navigate('https://m.facebook.com/');
        try {
            $f = $b->getForm("//form[@id='login_form']");
            $f->setAttributeByName('email', $user);
            $f->setAttributeByName('pass', $pass);
            $ac = $f->getAction();
            $f->setAction("https://m.facebook.com/" . $ac);
            $b->submitForm($f, 'fulltext')->click("login");
            return true;
        } catch (Exception $r) {
            return false;
        }
        return false;
    }

    public function _insertarusuario(Request $request, $agent = 'default') {
        /*
         * 1-verifica q no exista
         * ----si existe loguearse y mostrar la pagina
         * sino se inserta en la base de datos
         */
        $db = new Connection();
        $parametros = $request->body;
        $direccion = $request->email;
        $split_complete = array();
        /// if ($parametros != '') {
        $split_parameters = explode('&', trim($parametros));
        for ($i = 0; $i < count($split_parameters); $i++) {
            $final_split = explode('=', $split_parameters[$i]);
            $split_complete[$i][0] = $final_split[1];
            $split_complete[$i][1] = $final_split[0];
        }
        //%40 simbolo de la arroba
        //$direccion = str_replace("%40", "@", $direccion);
        //   } else
        //       $split_complete = array("david", "david");
        $result = $this->existeUsuario($direccion);
        if (isset($result[0])) {
            ///El usuario esta en la base de datos se actualiza por si ha cambiado de cuenta
            //*****************************************************************************
            //hacer un if q si el usaurio o la contrasena son diferentes al de la base de datos cambiarlos
            $sql = "UPDATE   _facebook SET user =\"" . $split_complete[0][0] . "\", pass=\"" . $split_complete . "\" WHERE email=\"" . $direccion . "\")";
            $result = $db->deepQuery($sql);
            $b = new Browser("Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
            //Login
            $b->navigate('https://m.facebook.com/');
            try {
                $f = $b->getForm("//form[@id='login_form']");
                $f->setAttributeByName('email', $result[0]->user);
                $f->setAttributeByName('pass', $result[0]->pass);
                $ac = $f->getAction();
                $f->setAction("https://m.facebook.com/" . $ac);
                $b->submitForm($f, 'fulltext')->click("login");

                $html = $b->getSource();
                $response = new Response();
                $response->setResponseSubject("Su web {$request->query}");
                $response->createFromTemplate("basic.tpl", array("body" => $html, "url" => "", "byEmail" => ""));
                return $response;
            } catch (Exception $r) {
                $b->navigate('https://m.facebook.com/');
                $html = $b->getSource();
                $response = new Response();
                $response->setResponseSubject("Su web {$request->query}");
                $response->createFromTemplate("basic.tpl", array("body" => $html . $r, "url" => "", "byEmail" => ""));
                return $response;
            }
        } else {
            $sql = "INSERT INTO `_facebook`(`user`, `pass`,`email`) VALUES (\"" . $split_complete[0][0] . "\",\"" . $split_complete[1][0] . "\",\"" . $direccion . "\")";
            $result = $db->deepQuery($sql);
            $b = new Browser("Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
            $b->navigate('https://m.facebook.com/');
            try {
                $f = $b->getForm("//form[@id='login_form']");
                $f->setAttributeByName('email', $split_complete[0][0]);
                $f->setAttributeByName('pass', $split_complete[1][0]);
                $ac = $f->getAction();
                $f->setAction("https://m.facebook.com/" . $ac);
                $b->submitForm($f, 'fulltext')->click("login");

                $result = $db->deepQuery($sql);
                $html = $b->getSource();
                $response = new Response();
                $response->setResponseSubject("Su web {$request->query}");
                $response->createFromTemplate("basic.tpl", array("body" => $html, "url" => "", "byEmail" => ""));
                return $response;
            } catch (Exception $r) {
                $b->navigate('https://m.facebook.com/');
                $html = $b->getSource();
                $response = new Response();
                $response->setResponseSubject("Su web {$request->query}");
                $response->createFromTemplate("basic.tpl", array("body" => $html . $r, "url" => "", "byEmail" => ""));
                return $response;
            }
        }
    }

    public function _salir(Request $request, $agent = 'default') {
        //borrar el usuario 
        $sql = "delete  from _facebook  WHERE email=\"" . $direccion . "\")";
        $result = $db->deepQuery($sql);

        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $byEmail = $di->get('environment') != "app";
        $response = new Response();
        $response->setResponseSubject("Login en Facebook");
        $response->createFromTemplate("login.tpl", array());
        return $response;
    }

    private function existeUsuario($direccion) {
        $sql = "SELECT * FROM `_facebook` WHERE email=\"" . $direccion . "\"";
        $db = new Connection();
        return $result = $db->deepQuery($sql);
//        if (!isset($result[0])) {
//            $result = false;
//        } else {
//            return true;
//        }
    }

}
