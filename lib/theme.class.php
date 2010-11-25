<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class ttheme extends tevents {
  public static $instances = array();
  public static $vars = array();
  public $name;
  public $parsing;
  public $templates;
  private $themeprops;
  
  public static function exists($name) {
    return file_exists(litepublisher::$paths->data . 'themes'. DIRECTORY_SEPARATOR . $name . '.php') ||
    file_exists(litepublisher::$paths->themes . $name . DIRECTORY_SEPARATOR  . 'about.ini');
  }
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  public static function getinstance($name) {
    if (isset(self::$instances[$name])) return self::$instances[$name];
    $result = getinstance(__class__);
    if ($result->name != '') $result = litepublisher::$classes->newinstance(__class__);
    $result->name = $name;
    $result->load();
    return $result;
  }
  
  protected function create() {
    parent::create();
    $this->name = '';
    $this->parsing = array();
    $this->data['type'] = 'litepublisher';
    $this->data['parent'] = '';
    $this->addmap('templates', array());
    $this->templates = array(
    0 => '',
    'title' => '',
    'menu' => array(),
    'content' => array(),
    'sidebars' => array()
    );
    $this->themeprops = new tthemeprops($this);
  }
  
  public function getbasename() {
    return 'themes' . DIRECTORY_SEPARATOR . $this->name;
  }
  
  public function __destruct() {
    unset($this->themeprops, self::$instances[$this->name], $this->templates);
    parent::__destruct();
  }
  public function load() {
    if ($this->name == '') return false;
    if (parent::load()) {
      self::$instances[$this->name] = $this;
      return true;
    }
    return $this->parsetheme();
  }
  
  public function parsetheme() {
    if (!file_exists(litepublisher::$paths->themes . $this->name . DIRECTORY_SEPARATOR  . 'about.ini')) {
      $this->error(sprintf('The %s theme not exists', $this->name));
    }
    
    $parser = tthemeparser::instance();
    if ($parser->parse($this)) {
      self::$instances[$this->name] = $this;
      $this->save();
    }else {
      $this->error("Theme file $filename not exists");
    }
  }
  
  public function __tostring() {
    return $this->templates[0];
  }
  
  public function __get($name) {
    if (array_key_exists($name, $this->templates)) {
      if (is_array($this->templates[$name])) {
        $this->themeprops->array = &$this->templates[$name];
        return $this->themeprops;
      } else {
        return $this->templates[$name];
      }
    } elseif ($name == 'comment') {
      $this->themeprops->array = &$this->templates['content']['post']['templatecomments']['comments']['comment'];
      return $this->themeprops;
    }
    
    return parent::__get($name);
  }
  
  public function __set($name, $value) {
    if (array_key_exists($name, $this->templates)) {
      if (is_array($this->templates[$name]) && isset($this->templates[$name][0]))  {
        $this->templates[$name][0] = $value;
      } else {
        $this->templates[$name] = $value;
      }
      return;
    }
    return parent::__set($name, $value);
  }
  
  public function getsidebarscount() {
    return count($this->templates['sidebars']);
  }
  
  
  private function getvar($name) {
    if ($name == 'site')  return litepublisher::$site;
    
    if (isset($GLOBALS[$name])) {
      $var =  $GLOBALS[$name];
    } else {
      $classes = litepublisher::$classes;
      if (isset($classes->classes[$name])) {
        $var = $classes->getinstance($classes->classes[$name]);
      } else {
        $class = 't' . $name;
        if (isset($classes->items[$class])) $var = $classes->getinstance($class);
      }
    }
    
    if (!isset($var)) {
      $var = $classes->gettemplatevar($name);
    }
    
    if (!is_object($var)) {
      litepublisher::$options->trace(sprintf('Object %s not found in %s', $name, $this->parsing[count($this->parsing) -1]));
      return false;
    }
    
    return $var;
  }
  
  public function parsecallback($names) {
    $name = $names[1];
    $prop = $names[2];
    if (isset(self::$vars[$name])) {
      $var =  self::$vars[$name];
    } elseif ($var = $this->getvar($name)) {
      self::$vars[$name] = $var;
    } else {
      return '';
    }
    
    try {
    return $var->{$prop};
    } catch (Exception $e) {
      //var_dump($this->parsing[count($this->parsing)-1]);
      litepublisher::$options->handexception($e);
    }
    return '';
  }
  
  public function parse($s) {
    $s = str_replace('$site.url', litepublisher::$site->url, (string) $s);
    array_push($this->parsing, $s);
    try {
      $s = preg_replace('/%%([a-zA-Z0-9]*+)_(\w\w*+)%%/', '\$$1.$2', $s);
      $result = preg_replace_callback('/\$(\w*+)\.(\w\w*+)/', array(&$this, 'parsecallback'), $s);
    } catch (Exception $e) {
      $result = '';
      litepublisher::$options->handexception($e);
    }
    array_pop($this->parsing);
    return $result;
  }
  
  public function parsearg($s, targs $args) {
    $s = $this->parse($s);
    return strtr ($s, $args->data);
  }
  
  public static function parsevar($name, $var, $s) {
    self::$vars[$name] = $var;
    $self = self::instance();
    return $self->parse($s);
  }
  
  public function gethtml($context) {
    self::$vars['context'] = $context;
    switch ($this->type) {
      case 'litepublisher':
      return $this->parse($this->templates[0]);
      
      case 'wordpress':
      return wordpress::getcontent();
    }
  }
  
  public function getnotfount() {
    return $this->parse($this->content->notfound);
  }
  
  public function getpages($url, $page, $count) {
    if (!(($count > 1) && ($page >=1) && ($page <= $count)))  return '';
    $from = 1;
    $to = $count;
    $perpage = litepublisher::$options->perpage;
    if ($count > $perpage * 2) {
      //$page is midle of the bar
      $from = max(1, $page - ceil($perpage / 2));
      $to = min($count, $from + $perpage);
    }
    $items = range($from, $to);
    if ($items[0] != 1) array_unshift($items, 1);
    if ($items[count($items) -1] != $count) $items[] = $count;
    $navi =$this->content->navi;
    $pageurl = rtrim($url, '/') . '/page/';
    $args = targs::instance();
    $a = array();
    foreach ($items as $i) {
      $args->page = $i;
      $args->url = $i == 1 ? $url : $pageurl .$i . '/';
      $a[] = $this->parsearg(($i == $page ? $navi->current : $navi->link), $args);
    }
    
    return str_replace('$items', implode($navi->divider, $a), (string) $navi);
  }
  
  public function getposts(array $items, $lite) {
    if (count($items) == 0) return '';
    if (dbversion) {
      $posts = tposts::instance();
      $posts->loaditems($items);
    }
    
    $result = '';
    $tml = $lite ? (string) $this->content->excerpts->lite->excerpt : (string) $this->content->excerpts->excerpt;
    foreach($items as $id) {
      self::$vars['post'] = tpost::instance($id);
      $result .= $this->parse($tml);
    }
    
    $tml = $lite ? (string) $this->content->excerpts->lite : (string) $this->content->excerpts;
    if ($tml == '') return $result;
    return str_replace('$excerpt', $result, $this->parse($tml));
  }
  
  public function getpostswidgetcontent(array $items, $sidebar, $tml) {
    if (count($items) == 0) return '';
    $result = '';
    if ($tml == '') $tml = $this->getwidgetitem('posts', $sidebar);
    
    foreach ($items as $id) {
      self::$vars['post'] = tpost::instance($id);
      $result .= $this->parse($tml);
    }
    return str_replace('$item', $result, $this->getwidgetitems('posts', $sidebar));
  }
  
  public function getwidgetcontent($items, $name, $sidebar) {
    return str_replace('$item', $items, $this->getwidgetitems($name, $sidebar));
  }
  
  public function getwidget($title, $content, $template, $sidebar) {
    $tml = $this->getwidgettemplate($template, $sidebar);
    $args = targs::instance();
    $args->title = $title;
    $args->items = $content;
    return $this->parsearg($tml, $args);
  }
  
  public function getwidgettemplate($name, $sidebar) {
    $sidebars = &$this->templates['sidebars'];
    if (!isset($sidebars[$sidebar][$name][0])) $name = 'widget';
    return $sidebars[$sidebar][$name][0];
  }
  
  public function  getwidgetitem($name, $index) {
    return $this->getwidgettml($index, $name, 'item');
  }
  
  public function  getwidgetitems($name, $index) {
    return $this->getwidgettml($index, $name, 'items');
  }
  
  public function  getwidgettml($index, $name, $tml) {
    $sidebars = &$this->templates['sidebars'];
    if (isset($sidebars[$index][$name][$tml])) return $sidebars[$index][$name][$tml];
    if ($index >= count($sidebars)) {
      $index = count($sidebars) - 1;
      if (isset($sidebars[$index][$name][$tml])) return $sidebars[$index][$name][$tml];
    }
    if (isset($sidebars[$index]['widget'][$tml])) return $sidebars[$index]['widget'][$tml];
    $this->error("Unknown widget '$name' and template '$tml' in $index sidebar");
  }
  
  public function simple($content) {
    return str_replace('$content', $content, $this->content->simple);
  }
  
  public static function clearcache() {
    tfiler::delete(litepublisher::$paths->data . 'themes', false, false);
    litepublisher::$urlmap->clearcache();
  }
  
}//class

class tthemeprops {
  public $array;
  private $_theme;
  
  public function __construct(ttheme $theme) {
    $this->_theme = $theme;
    $this->array = &$theme->templates;
  }
  
  public function __destruct() {
    unset($this->_theme );
    unset($this->array);
  }
  
  
  
  public function __get($name) {
    if (!isset($this->array[$name])) {
      litepublisher::$options->trace("$name not found\n" . implode("\n", array_keys($this->array)));
      litepublisher::$options->showerrors();
    }
    
    if (is_array($this->array[$name])) {
      $this->array = &$this->array[$name];
      return $this;
    }
    return $this->array[$name];
  }
  
public function __set($name, $value) {$this->array[$name] = $value; }
  
  public function __call($name, $params) {
    if (isset($params[0]) && is_object($params[0]) && ($params[0] instanceof targs)) {
      return $this->_theme->parsearg( (string) $this->$name, $params[0]);
    } else {
      return $this->_theme->parse((string) $this->$name);
    }
  }
  
  public function __tostring() {
    if (!isset($this->array[0])) {
      litepublisher::$options->trace(implode("\n", array_keys(($this->array))));
      litepublisher::$options->showerrors();
    }
    
    return $this->array[0];
  }
  public function __isset($name) {
    return array_key_exists($name, $this->array);
  }
}//class


?>