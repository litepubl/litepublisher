<?php
/**
* Lite Publisher
* Copyright (C) 2010 - 2013 Vladimir Yushko http://litepublisher.ru/ http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tjsonserver extends tevents {
  public $debug;
  
  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'jsonserver';
    $this->cache = false;
    $this->addevents('beforerequest', 'beforecall', 'aftercall');
    $this->data['eventnames'] = &$this->eventnames;
    $this->map['eventnames'] = 'eventnames';
    $this->data['url'] = '/admin/jsonserver.php';
    $this->debug = false;
  }
  
  public function getpostbody() {
    global$HTTP_RAW_POST_DATA;
    if ( !isset( $HTTP_RAW_POST_DATA ) ) {
      $HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
    }
    if ( isset($HTTP_RAW_POST_DATA) ) {
      $HTTP_RAW_POST_DATA = trim($HTTP_RAW_POST_DATA);
    }
    
    if (litepublisher::$debug) {
      tfiler::log("request:\n" . $HTTP_RAW_POST_DATA, 'json.txt');
/*
      $reqname = litepublisher::$paths->data . 'logs' . DIRECTORY_SEPARATOR  . 'request.json';
      file_put_contents($reqname, $HTTP_RAW_POST_DATA);
      @chmod($reqname, 0666);
      $HTTP_RAW_POST_DATA = file_get_contents($GLOBALS['paths']['home'] . 'raw.txt');
*/
    }
    
    return $HTTP_RAW_POST_DATA;
  }
  
  public function get_json_args() {
    if ($s = trim($this->getpostbody())) {
      return json_decode($s, true);
    }
    return false;
  }
  
  public function getargs() {
    if (isset($_GET['method'])) return $_GET;

if (isset($_POST['method'])) {
      tguard::post();
return $_POST;
}

if (isset($_POST['json'])) {
      tguard::post();
    if (($s = trim($_POST['json'])) && ($args = json_decode($s, true))) {
if (isset($args['method'])) return $args;
}
}

if ($args = $this->get_json_args()) {
      if (isset($args['method'])) return $args;
      }

      return false;
    }
    
  public function request($idurl) {
    $this->beforerequest();
$args = $this->getargs();    
if (!$args || !isset($args['method'])) return 403;

$rpc = isset($args['jsonrpc']) ? ($args['jsonrpc'] == '2.0') : false;
$id =$rpc && isset($args['id']) ? $args['id'] : false;

    if (!isset($this->events[$args['method']])) {
if (!$rpc) return 403;

$result = array(
'jsonrpc' => '2.0',
'error' => array(
'code' =>       404,
'message' => sprintf('Method "%s" not found', $args['method']),
));

if ($id) $result['id'] = $id;
return $this->json($result);
}

$params = $rpc && isset($args['params']) ? $args['params'] : $args;
    if (isset($params['litepubl_user'])) $_COOKIE['litepubl_user'] = $params['litepubl_user'];
    if (isset($params['litepubl_user_id'])) $_COOKIE['litepubl_user_id'] = $params['litepubl_user_id'];

    $a = array(&$params);
    $this->callevent('beforecall', $a);
    try {
      $result = $this->callevent($args['method'], $a);
    } catch (Exception $e) {
      if (litepublisher::$debug || $this->debug) {
        litepublisher::$options->handexception($e);
        throw new Exception(litepublisher::$options->errorlog);
      }

$result = array(
'jsonrpc' => '2.0',
'error' => array(
'code' =>       $e->getCode(),
'message' => $e->getMessage()
),
);

if ($id) $result['id'] = $args['id'];
return $this->json(array($result);
    }

    $this->callevent('aftercall', array(&$result, $args));
if ($rpc) {
$result = array(
'jsonrpc' => '2.0',
'result' => $result
);

if ($id) $result['id'] = $id;
}
return $this->json($result);
}

public function json($result) {
    $js = tojson($result);
    //if (litepublisher::$debug) tfiler::log("response:\n".$js, 'json.txt');
    
    return "<?php
    header('Connection: close');
    header('Content-Length: ". strlen($js) . "');
    header('Content-Type: text/javascript; charset=utf-8');
    header('Date: ".date('r') . "');
    Header( 'Cache-Control: no-cache, must-revalidate');
    Header( 'Pragma: no-cache');
    ?>" .
 $js;
    
    //header('Content-Type: application/json');
  }
  
  public function addevent($name, $class, $func) {
    if (!in_array($name, $this->eventnames)) $this->eventnames[] = $name;
    return parent::addevent($name, $class, $func);
  }
  
  public function delete_event($name) {
    if (isset($this->events[$name])) {
      unset($this->events[$name]);
      array_delete_value($this->eventnames, $name);
      $this->save();
    }
  }
  
}//class