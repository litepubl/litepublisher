<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/
//local.class.php
class targs {
  public $data;
  
  public static function instance() {
    return litepublisher::$classes->newinstance(__class__);
  }
  
  public function __construct($thisthis = null) {
    $site = litepublisher::$site;
    $this->data = array(
    '$site.url' => $site->url,
  '{$site.q}' => $site->q,
    '$site.q' => $site->q,
    '$site.files' => $site->files
    );
    if (isset($thisthis)) $this->data['$this'] = $thisthis;
  }
  
  public function __get($name) {
    if (($name == 'link') && !isset($this->data['$link'])  && isset($this->data['$url'])) {
      return litepublisher::$site->url . $this->data['$url'];
    }
    return $this->data['$' . $name];
  }
  
  public function __set($name, $value) {
    if (is_bool($value)) {
      $value = $value ? 'checked="checked"' : '';
    }
    
    $this->data['$'.$name] = $value;
    $this->data["%%$name%%"] = $value;
    
    if (($name == 'url') && !isset($this->data['$link'])) {
      $this->data['$link'] = litepublisher::$site->url . $value;
      $this->data['%%link%%'] = litepublisher::$site->url . $value;
    }
  }
  
  public function add(array $a) {
    foreach ($a as $key => $value) {
      $this->__set($key, $value);
      if ($key == 'url') {
        $this->data['$link'] = litepublisher::$site->url . $value;
        $this->data['%%link%%'] = litepublisher::$site->url . $value;
      }
    }
    
    if (isset($a['title']) && !isset($a['text'])) $this->__set('text', $a['title']);
    if (isset($a['text']) && !isset($a['title']))  $this->__set('title', $a['text']);
  }
  
}//class

class tlocal {
  public static $data;
  private static $files;
  public $section;
  
  public function __get($name) {
    if (isset(self::$data[$this->section][$name])) return self::$data[$this->section][$name];
    if (isset(self::$data['common'][$name])) return self::$data['common'][$name];
    if (isset(self::$data['default'][$name])) return self::$data['default'][$name];
    return '';
  }
  
  public function __call($name, $args) {
    return strtr ($this->__get($name), $args->data);
  }
  
  public static function instance($section = '') {
    $result = getinstance(__class__);
    if ($section != '') $result->section = $section;
    return $result;
  }
  
  public static function date($date, $format = '') {
    if (empty($format)) $format = self::getdateformat();
    return self::translate(date($format, $date), 'datetime');
  }
  
  public static function getdateformat() {
    $format = litepublisher::$options->dateformat;
    return $format != ''? $format : self::$data['datetime']['dateformat'];
  }
  
  public static function translate($s, $section = 'default') {
    return strtr($s, self::$data[$section]);
  }
  
  public static function checkload() {
    if (!isset(self::$data)) {
      self::$data = array();
      self::$files = array();
      if (litepublisher::$options->installed) self::loadlang('');
    }
  }
  
  public static function loadlang($name) {
    $langname = litepublisher::$options->language;
    if ($langname != '') {
      if ($name != '') $name = '.' . $name;
      self::load(litepublisher::$paths->languages . $langname . $name);
    }
  }
  
  public static function load($filename) {
    if (in_array($filename, self::$files)) return;
    self::$files[] = $filename;
    $cachefilename = self::getcachefilename(basename($filename));
    if (tfilestorage::loadvar($cachefilename, $v) && is_array($v)) {
      self::$data = $v + self::$data ;
    } else {
      $v = parse_ini_file($filename . '.ini', true);
      self::$data = $v + self::$data ;
      tfilestorage::savevar($cachefilename, $v);
      self::ini2js($filename);
    }
  }
  
  public static function ini2js($filename) {
  $js = "var lang;\nif (lang == undefined) lang = {};\n";
    $base = basename($filename);
    if (strend($base, '.admin')) {
      $js .= sprintf('lang.comments = %s;',  json_encode(self::$data['comments']));
    } else {
      $js .= sprintf('lang.comment = %s;',  json_encode(self::$data['comment']));
    }
    file_put_contents(litepublisher::$paths->files . $base . '.js', $js);
    @chmod($filename, 0666);
  }
  
  public static function loadini($filename) {
    if (in_array($filename, self::$files)) return;
    if (file_exists($filename) && ($v = parse_ini_file($filename, true))) {
      self::$data = $v + self::$data ;
      self::$files[] = $filename;
    }
  }
  
  public static function install() {
    $dir =litepublisher::$paths->data . 'languages';
    if (!is_dir($dir)) @mkdir($dir, 0777);
    @chmod($dir, 0777);
    self::checkload();
  }
  
  public static function getcachedir() {
    return litepublisher::$paths->data . 'languages' . DIRECTORY_SEPARATOR;
  }
  
  public static function clearcache() {
    tfiler::delete(self::getcachedir(), false, false);
    self::$files = array();
  }
  
  public static function getcachefilename($name) {
    return self::getcachedir() . $name;
  }
  
  public static function loadsection($name, $section, $dir) {
    tlocal::loadlang($name);
    if (!isset(self::$data[$section])) {
      $language = litepublisher::$options->language;
      if ($name != '') $name = '.' . $name;
      self::loadini($dir . $language . $name . '.ini');
      tfilestorage::savevar(self::getcachefilename($language . $name), self::$data);
    }
  }
  
  public static function loadinstall() {
    self::loadini(litepublisher::$paths->languages . litepublisher::$options->language . '.install.ini');
  }
  
}//class

class tdateformater {
  public  $date;
public function __construct($date) { $this->date = $date; }
public function __get($name) { return tlocal::translate(date($name, $this->date), 'datetime'); }
}

//init
tlocal::checkload();

//views.class.php
class tview extends titem {
  public $sidebars;
  private $themeinstance;
  
  public static function instance($id = 1) {
    return parent::iteminstance(__class__, $id);
  }
  
  public static function getinstancename() {
    return 'view';
  }
  
  public static function getview($instance) {
    $id = $instance->getidview();
    if (isset(self::$instances['view'][$id]))     return self::$instances['view'][$id];
    $views = tviews::instance();
    if (!$views->itemexists($id)) {
      $id = 1; //default, wich always exists
      $instance->setidview($id);
    }
    return self::instance($id);
  }
  
  protected function create() {
    parent::create();
    $this->data = array(
    'id' => 0,
    'name' => 'default',
    'themename' => 'default',
    'customsidebar' => false,
    'disableajax' => false,
    'custom' => array(),
    'sidebars' => array()
    );
    $this->sidebars = &$this->data['sidebars'];
    $this->themeinstance = null;
  }
  
  public function __destruct() {
    unset($this->themeinstance);
    parent::__destruct();
  }
  
  public function load() {
    $views = tviews::instance();
    if ($views->itemexists($this->id)) {
      $this->data = &$views->items[$this->id];
      $this->sidebars = &$this->data['sidebars'];
      return true;
    }
    return false;
  }
  
  public function save() {
    return tviews::instance()->save();
  }
  
  public function setthemename($name) {
    if ($name != $this->themename) {
      if (!ttheme::exists($name)) return $this->error(sprintf('Theme %s not exists', $name));
      $this->data['themename'] = $name;
      $this->themeinstance = ttheme::getinstance($name);
      $this->data['custom'] = $this->themeinstance->templates['custom'];
      $this->save();
      tviews::instance()->themechanged($this);
    }
  }
  
  public function gettheme() {
    if (isset($this->themeinstance)) return $this->themeinstance;
    if (ttheme::exists($this->themename)) {
      $this->themeinstance = ttheme::getinstance($this->themename);
      if (count($this->data['custom']) == count($this->themeinstance->templates['custom'])) {
        $this->themeinstance->templates['custom'] = $this->data['custom'];
      } else {
        $this->data['custom'] = $this->themeinstance->templates['custom'];
        $this->save();
      }
    } else {
      $this->setthemename('default');
    }
    return $this->themeinstance;
  }
  
  public function setcustomsidebar($value) {
    if ($value != $this->customsidebar) {
      if ($this->id == 1) return false;
      if ($value) {
        $default = tview::instance(1);
        $this->sidebars = $default->sidebars;
      } else {
        $this->sidebars = array();
      }
      $this->data['customsidebar'] = $value;
      $this->save();
    }
  }
  
}//class

class tviews extends titems_storage {
  public $defaults;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    $this->dbversion = false;
    parent::create();
    $this->basename = 'views';
    $this->addevents('themechanged');
    $this->addmap('defaults', array());
  }
  
  public function add($name) {
    $this->lock();
    $id = ++$this->autoid;
    $view = litepublisher::$classes->newitem(tview::getinstancename(), 'tview', $id);
    $view->id = $id;
    $view->name = $name;
    $this->items[$id] = &$view->data;
    $this->unlock();
    return $id;
  }
  
  public function delete($id) {
    if ($id == 1) return $this->error('You cant delete default view');
    foreach ($this->defaults as $name => $iddefault) {
      if ($id == $iddefault) $this->defaults[$name] = 1;
    }
    return parent::delete($id);
  }
  
  public function widgetdeleted($idwidget) {
    $deleted = false;
    foreach ($this->items as &$viewitem) {
      unset($sidebar);
      foreach ($viewitem['sidebars'] as &$sidebar) {
        for ($i = count($sidebar) - 1; $i >= 0; $i--) {
          if ($idwidget == $sidebar[$i]['id']) {
            array_delete($sidebar, $i);
            $deleted = true;
          }
        }
      }
    }
    if ($deleted) $this->save();
  }
  
}//class


class tevents_itemplate extends tevents {
  
  protected function create() {
    parent::create();
    $this->data['idview'] = 1;
  }
  
public function gethead() {}
public function getkeywords() {}
public function getdescription() {}
  
  public function getidview() {
    return $this->data['idview'];
  }
  
  public function setidview($id) {
    if ($id != $this->idview) {
      $this->data['idview'] = $id;
      $this->save();
    }
  }
  
  public function getview() {
    return tview::getview($this);
  }
  
}//class


class titems_itemplate extends titems {
  
  protected function create() {
    parent::create();
    $this->data['idview'] = 1;
    $this->data['keywords'] = '';
    $this->data['description'] = '';
  }
  
public function gethead() {}
  public function getkeywords() {
    return $this->data['keywords'];
  }
  
  public function getdescription() {
    return $this->data['description'];
  }
  
  public function getidview() {
    return $this->data['idview'];
  }
  
  public function setidview($id) {
    if ($id != $this->data['idview']) {
      $this->data['idview'] = $id;
      $this->save();
    }
  }
  
  public function getview() {
    return tview::getview($this);
  }
  
}//class

//template.class.php
class ttemplate extends tevents_storage {
  public $path;
  public $url;
  public $context;
  public $itemplate;
  public $view;
  public $ltoptions;
  public $hover;
  //public $footer;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    //prevent recursion
    litepublisher::$classes->instances[__class__] = $this;
    parent::create();
    $this->basename = 'template' ;
    $this->addevents('beforecontent', 'aftercontent', 'onhead', 'onbody', 'on');
    $this->path = litepublisher::$paths->themes . 'default' . DIRECTORY_SEPARATOR ;
    $this->url = litepublisher::$site->files . '/themes/default';
    $this->itemplate = false;
    $this->ltoptions = array(0 =>
    sprintf("url: '%1\$s',\nfiles: '%2\$s',\nidurl: '%3\$s'",
    litepublisher::$site->url, litepublisher::$site->files, litepublisher::$urlmap->itemrequested['id']));
    $this->hover = true;
    $this->data['hovermenu'] = true;
    $this->data['heads'] = '';
    $this->data['js'] = '<script type="text/javascript" src="%s"></script>';
  $this->data['jsready'] = '<script type="text/javascript">$(document).ready(function() {%s});</script>';
    $this->data['jsload'] = '<script type="text/javascript">$.getScript(%s);</script>';
    $this->data['footer']=   '<a href="http://litepublisher.com/">Powered by Lite Publisher</a>';
    $this->data['tags'] = array();
  }
  
  public function __get($name) {
    if (method_exists($this, $get = 'get' . $name)) return $this->$get();
    if (array_key_exists($name, $this->data)) return $this->data[$name];
    if (preg_match('/^sidebar(\d)$/', $name, $m)) {
      $widgets = twidgets::instance();
      return $widgets->getsidebarindex($this->context, $this->view, (int) $m[1]);
    }
    
    if (array_key_exists($name, $this->data['tags'])) {
      $tags = ttemplatetags::instance();
      return $tags->__get($name);
    }
    if (isset($this->context) && isset($this->context->$name)) return $this->context->$name;
    return parent::__get($name);
  }
  
  public function request($context) {
    $this->context = $context;
    ttheme::$vars['context'] = $context;
    ttheme::$vars['template'] = $this;
    $this->itemplate = $context instanceof itemplate;
    $this->view = $this->itemplate ? tview::getview($context) : tview::instance();
    $theme = $this->view->theme;
    litepublisher::$classes->instances[get_class($theme)] = $theme;
    $this->path = litepublisher::$paths->themes . $theme->name . DIRECTORY_SEPARATOR ;
    $this->url = litepublisher::$site->files . '/themes/' . $theme->name;
    $this->hover = $this->hovermenu && ($theme->templates['menu.hover'] == 'true');
    $this->ltoptions[] = sprintf('themename: \'%s\'',  $theme->name);
    $result = $this->httpheader();
    $result  .= $theme->gethtml($context);
    unset(ttheme::$vars['context'], ttheme::$vars['template']);
    return $result;
  }
  
  protected function  httpheader() {
    if (method_exists($this->context, 'httpheader')) {
      $result= $this->context->httpheader();
      if (!empty($result)) return $result;
    }
    return turlmap::htmlheader($this->context->cache);
  }
  
  //html tags
  public function getsidebar() {
    $widgets = twidgets::instance();
    return $widgets->getsidebar($this->context, $this->view);
  }
  
  public function gettitle() {
    $title = $this->itemplate ? $this->context->gettitle() : '';
    $args = targs::instance();
    $args->title = $title;
    $theme = $this->view->theme;
    $result = $theme->parsearg($theme->title, $args);
    $result = trim($result, sprintf(' |.:%c%c', 187, 150));
    if ($result == '') return litepublisher::$site->name;
    return $result;
  }
  
  public function geticon() {
    $result = '';
    if (isset($this->context) && isset($this->context->icon)) {
      $icon = $this->context->icon;
      if ($icon > 0) {
        $files = tfiles::instance();
        if ($files->itemexists($icon)) $result = $files->geturl($icon);
      }
    }
    if ($result == '')  return litepublisher::$site->files . '/favicon.ico';
    return $result;
  }
  
  public function getkeywords() {
    $result = $this->itemplate ? $this->context->getkeywords() : '';
    if ($result == '')  return litepublisher::$site->keywords;
    return $result;
  }
  
  public function getdescription() {
    $result = $this->itemplate ? $this->context->getdescription() : '';
    if ($result =='') return litepublisher::$site->description;
    return $result;
  }
  
  public function getmenu() {
    $current = $this->context instanceof tmenu ? $this->context->id : 0;
    $filename = litepublisher::$paths->cache . $this->view->theme->name . '.' . $current;
    $filename .= litepublisher::$urlmap->adminpanel ?
    '.' . litepublisher::$options->group . '.adminmenu.php'
    : '.menu.php';
    if (file_exists($filename)) return file_get_contents($filename);
    
    $menus = litepublisher::$urlmap->adminpanel ? tadminmenus::instance() : tmenus::instance();
    $result = $menus->getmenu($this->hover, $current);
    file_put_contents($filename, $result);
    @chmod($filename, 0666);
    return $result;
  }
  
  public function sethovermenu($value) {
    if ($value == $this->hovermenu)  return;
    $this->data['hovermenu'] = $value;
    $this->save();
    
    litepublisher::$urlmap->clearcache();
  }
  
  private function getltoptions() {
    $result = "<script type=\"text/javascript\">\nvar ltoptions = {\n";
      $result .= implode(",\n", $this->ltoptions);
    $result .= "\n};\n</script>\n";
    return $result;
  }
  
  public function getjavascript($filename) {
    return sprintf($this->js, litepublisher::$site->files . $filename);
  }
  
  public function getready($s) {
    return sprintf($this->jsready, $s);
  }
  
  public function getloadjavascript($s) {
    return sprintf($this->jsload, $s);
  }
  
  
  public function addtohead($s) {
    $s = trim($s);
    if (false === strpos($this->heads, $s)) {
      $this->heads = trim($this->heads) . "\n" . $s;
      $this->save();
    }
  }
  
  public function deletefromhead($s) {
    $s = trim($s);
    $i = strpos($this->heads, $s);
    if (false !== $i) {
      $this->heads = substr_replace($this->heads, '', $i, strlen($s));
      $this->heads = trim(str_replace("\n\n", "\n", $this->heads));
      $this->save();
    }
  }
  
  public function gethead() {
    $result = $this->heads;
    if ($this->itemplate) $result .= $this->context->gethead();
    $result = $this->getltoptions() . $result;
    $result = $this->view->theme->parse($result);
    $this->callevent('onhead', array(&$result));
    return $result;
  }
  
  public function getbody() {
    $result = '';
    $this->callevent('onbody', array(&$result));
    return $result;
  }
  
  public function getcontent() {
    $result = '';
    $this->callevent('beforecontent', array(&$result));
    $result .= $this->itemplate ? $this->context->getcont() : '';
    $this->callevent('aftercontent', array(&$result));
    return $result;
  }
  
  protected function setfooter($s) {
    if ($s != $this->data['footer']) {
      $this->data['footer'] = $s;
      $this->Save();
    }
  }
  
  public function getpage() {
    $page = litepublisher::$urlmap->page;
    if ($page <= 1) return '';
    return sprintf(tlocal::$data['default']['pagetitle'], $page);
  }
  
}//class

//theme.class.php
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
  
  public static function getwidgetnames() {
    return array('submenu', 'categories', 'tags', 'archives', 'links', 'posts', 'comments', 'friends', 'meta') ;
  }
  
  protected function create() {
    parent::create();
    $this->name = '';
    $this->parsing = array();
    $this->data['type'] = 'litepublisher';
    $this->data['parent'] = '';
    $this->addmap('templates', array());
    $this->templates = array(
    'index' => '',
    'title' => '',
    'menu' => '',
    'content' => '',
    'sidebars' => array(),
    'custom' => array(),
    'customadmin' => array()
    );
    $this->themeprops = new tthemeprops($this);
  }
  
  public function __destruct() {
    unset($this->themeprops, self::$instances[$this->name], $this->templates);
    parent::__destruct();
  }
  
  public function getbasename() {
    return 'themes' . DIRECTORY_SEPARATOR . $this->name;
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
      $this->error(sprintf('Theme file %s not exists', $filename));
    }
  }
  
  public function __tostring() {
    return $this->templates[0];
  }
  
  public function __get($name) {
    if (array_key_exists($name, $this->templates)) return $this->themeprops->setpath($name);
    if ($name == 'comment') return $this->themeprops->setpath('content.post.templatecomments.comments.comment');
    if ($name == 'sidebar') return $this->themeprops->setroot($this->templates['sidebars'][0]);
    if (preg_match('/^sidebar(\d)$/', $name, $m)) return $this->themeprops->setroot($this->templates['sidebars'][$m[1]]);
    return parent::__get($name);
  }
  
  public function __set($name, $value) {
    if (array_key_exists($name, $this->templates)) {
      $this->templates[$name] = $value;
      return;
    }
    return parent::__set($name, $value);
  }
  
  public function gettag($path) {
    if (!array_key_exists($path, $this->templates)) $this->error(sprintf('Path "%s" not found', $path));
    $this->themeprops->setpath($path);
    $this->themeprops->tostring = true;
    return $this->themeprops;
  }
  
  public function reg($exp) {
    if (!strpos($exp, '\.')) $exp = str_replace('.', '\.', $exp);
    $result = array();
    foreach ($this->templates as $name => $val) {
      if (preg_match($exp, $name)) $result[$name] = $val;
    }
    return $result;
  }
  
  public function getsidebarscount() {
    return count($this->templates['sidebars']);
  }
  
  private function getvar($name) {
    if ($name == 'site')  return litepublisher::$site;
    if ($name == 'lang') return tlocal::instance();
    if (isset($GLOBALS[$name])) {
      $var =  $GLOBALS[$name];
    } else {
      $classes = litepublisher::$classes;
      $var = $classes->gettemplatevar($name);
      if (!$var) {
        if (isset($classes->classes[$name])) {
          $var = $classes->getinstance($classes->classes[$name]);
        } else {
          $class = 't' . $name;
          if (isset($classes->items[$class])) $var = $classes->getinstance($class);
        }
      }
    }
    
    if (!is_object($var)) {
      litepublisher::$options->trace(sprintf('Object "%s" not found in %s', $name, $this->parsing[count($this->parsing) -1]));
      return false;
    }
    
    return $var;
  }
  
  public function parsecallback($names) {
    $name = $names[1];
    $prop = $names[2];
    if (isset(self::$vars[$name])) {
      $var =  self::$vars[$name];
    } elseif ($name == 'custom') {
      return $this->parse($this->templates['custom'][$prop]);
    } elseif ($var = $this->getvar($name)) {
      self::$vars[$name] = $var;
    } else {
      return '';
    }
    
    try {
    return $var->{$prop};
    } catch (Exception $e) {
      litepublisher::$options->handexception($e);
    }
    return '';
  }
  
  public function parse($s) {
    $s = str_replace('$site.url', litepublisher::$site->url, (string) $s);
    array_push($this->parsing, $s);
    try {
      $s = preg_replace('/%%([a-zA-Z0-9]*+)_(\w\w*+)%%/', '\$$1.$2', $s);
      $result = preg_replace_callback('/\$([a-zA-Z]\w*+)\.(\w\w*+)/', array(&$this, 'parsecallback'), $s);
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
  
  public function replacelang($s, $lang) {
    $s = preg_replace('/%%([a-zA-Z0-9]*+)_(\w\w*+)%%/', '\$$1.$2', (string) $s);
    self::$vars['lang'] = isset($lang) ? $lang : tlocal::instance('default');
    $s = strtr($s, array(
    '$site.url' => litepublisher::$site->url,
    '$site.files' => litepublisher::$site->files,
  '{$site.q}' => litepublisher::$site->q
    ));
    
    if (preg_match_all('/\$lang\.(\w\w*+)/', $s, $m, PREG_SET_ORDER)) {
      foreach ($m as $item) {
      if ($v = $lang->{$item[1]}) {
          $s = str_replace($item[0], $v, $s);
        }
      }
    }
    return $s;
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
      return $this->parse($this->templates['index']);
      
      case 'wordpress':
      return wordpress::getcontent();
    }
  }
  
  public function getnotfount() {
    return $this->parse($this->content->notfound);
  }
  
  public function getpages($url, $page, $count) {
    if (!(($count > 1) && ($page >=1) && ($page <= $count)))  return '';
    $args = targs::instance();
    $args->count = $count;
    $from = 1;
    $to = $count;
    $perpage = litepublisher::$options->perpage;
    $args->perpage = $perpage;
    if ($count > $perpage * 2) {
      //$page is midle of the bar
      $from = max(1, $page - ceil($perpage / 2));
      $to = min($count, $from + $perpage);
    }
    $items = range($from, $to);
    if ($items[0] != 1) array_unshift($items, 1);
    if ($items[count($items) -1] != $count) $items[] = $count;
    $currenttml=$this->templates['content.navi.current'];
    $tml =$this->templates['content.navi.link'];
    if (!strbegin($url, 'http')) $url = litepublisher::$site->url . $url;
    $pageurl = rtrim($url, '/') . '/page/';
    
    $a = array();
    foreach ($items as $i) {
      $args->page = $i;
      $args->link = $i == 1 ? $url : $pageurl .$i . '/';
      $a[] = $this->parsearg(($i == $page ? $currenttml : $tml), $args);
    }
    
    $args->link =$url;
    $args->pageurl = $pageurl;
    $args->page = $page;
    $args->items = implode($this->templates['content.navi.divider'], $a);
    return $this->parsearg($this->templates['content.navi'], $args);
  }
  
  public function getposts(array $items, $lite) {
    if (count($items) == 0) return '';
    if (dbversion) {
      $posts = tposts::instance();
      $posts->loaditems($items);
    }
    
    $result = '';
    self::$vars['lang'] = tlocal::instance('default');
    $tml = $lite ? $this->templates['content.excerpts.lite.excerpt'] : $this->templates['content.excerpts.excerpt'];
    foreach($items as $id) {
      self::$vars['post'] = tpost::instance($id);
      $result .= $this->parse($tml);
    }
    
    $tml = $lite ? $this->templates['content.excerpts.lite'] : $this->templates['content.excerpts'];
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
    unset(self::$vars['post']);
    return str_replace('$item', $result, $this->getwidgetitems('posts', $sidebar));
  }
  
  public function getwidgetcontent($items, $name, $sidebar) {
    return str_replace('$item', $items, $this->getwidgetitems($name, $sidebar));
  }
  
  public function getwidget($title, $content, $template, $sidebar) {
    $args = targs::instance();
    $args->title = $title;
    $args->items = $content;
    return $this->parsearg($this->getwidgettml($sidebar, $template, ''), $args);
  }
  
  public function  getwidgetitem($name, $index) {
    return $this->getwidgettml($index, $name, 'item');
  }
  
  public function  getwidgetitems($name, $index) {
    return $this->getwidgettml($index, $name, 'items');
  }
  
  public function  getwidgettml($index, $name, $tml) {
    $count = count($this->templates['sidebars']);
    if ($index >= $count) $index = $count - 1;
    $widgets = &$this->templates['sidebars'][$index];
    if (($tml != '') && ($tml [0] != '.')) $tml = '.' . $tml;
    if (isset($widgets[$name . $tml])) return $widgets[$name . $tml];
    if (isset($widgets['widget' . $tml])) return $widgets['widget'  . $tml];
    $this->error("Unknown widget '$name' and template '$tml' in $index sidebar");
  }
  
  public function simple($content) {
    return str_replace('$content', $content, $this->content->simple);
  }
  
  public static function clearcache() {
    tfiler::delete(litepublisher::$paths->data . 'themes', false, false);
    litepublisher::$urlmap->clearcache();
  }
  
  public static function getwidgetpath($path) {
    if ($path === '') return '';
    switch ($path) {
      case '.items':
      return '.items';
      
      case '.items.item':
      case '.item':
      return '.item';
      
      case '.items.item.subitems':
      case '.item.subitems':
      case '.subitems':
      return '.subitems';
      
      case '.classes':
      case '.items.classes':
      return  '.classes';
    }
    
    return false;
  }
  
}//class

class tthemeprops {
  
  public $path;
  public $tostring;
  private $root;
  private $theme;
  
  public function __construct(ttheme $theme) {
    $this->theme = $theme;
    $this->root = &$theme->templates;
    $this->path = '';
    $this->tostring = false;
  }
  
  public function __destruct() {
    unset($this->theme, $this->root);
  }
  
  public function error($path) {
    litepublisher::$options->trace(sprintf('Path "%s" not found', $path));
    litepublisher::$options->showerrors();
  }
  
  public function getpath($name) {
    return $this->path == '' ? $name : $this->path . '.' . $name;
  }
  
  public function setpath($path) {
    $this->root = &$this->theme->templates;
    $this->path = $path;
    $this->tostring = false;
    return $this;
  }
  
  public function setroot(array &$root) {
    $this->setpath('');
    $this->root = &$root;
    return $this;
  }
  
  public function __get($name) {
    $path = $this->getpath($name);
    if (!array_key_exists($path, $this->root)) $this->error($path);
    if ($this->tostring) return $this->root[$path];
    $this->path = $path;
    return $this;
  }
  
  public function __set($name, $value) {
    $this->root[$this->getpath($name)] = $value;
  }
  
  public function __call($name, $params) {
    if (isset($params[0]) && is_object($params[0]) && ($params[0] instanceof targs)) {
      return $this->theme->parsearg( (string) $this->$name, $params[0]);
    } else {
      return $this->theme->parse((string) $this->$name);
    }
  }
  
  public function __tostring() {
    if (array_key_exists($this->path, $this->root)) {
      return $this->root[$this->path];
    } else {
      $this->error($this->path);
    }
  }
  
  public function __isset($name) {
    return array_key_exists($this->getpath($name), $this->root);
  }
  
}//class

//widgets.class.php
class twidget extends tevents {
  public $id;
  public $template;
  protected $adminclass;
  
  protected function create() {
    parent::create();
    $this->basename = 'widget';
    $this->cache = 'cache';
    $this->id = 0;
    $this->template = 'widget';
    $this->adminclass = 'tadminwidget';
  }
  
  public function addtosidebar($sidebar) {
    $widgets = twidgets::instance();
    $id = $widgets->add($this);
    $sidebars = tsidebars::instance();
    $sidebars->insert($id, false, $sidebar, -1);
    
    litepublisher::$urlmap->clearcache();
    return $id;
  }
  
  protected function getadmin() {
    if (($this->adminclass != '') && class_exists($this->adminclass)) {
      $admin = getinstance($this->adminclass);
      $admin->widget = $this;
      return $admin;
    }
    $this->error(sprintf('The "%s" admin class not found', $this->adminclass));
  }
  
  public function getwidget($id, $sidebar) {
    try {
      $title = $this->gettitle($id);
      $content = $this->getcontent($id, $sidebar);
    } catch (Exception $e) {
      litepublisher::$options->handexception($e);
      return '';
    }
    
    $theme = ttheme::instance();
    return $theme->getwidget($title, $content, $this->template, $sidebar);
  }
  
  public function getdeftitle() {
    return '';
  }
  
  public function gettitle($id) {
    if (!isset($id)) $this->error('no id');
    $widgets = twidgets::instance();
    if (isset($widgets->items[$id])) {
      return $widgets->items[$id]['title'];
    }
    return $this->getdeftitle();
  }
  
  public function settitle($id, $title) {
    $widgets = twidgets::instance();
    if (isset($widgets->items[$id]) && ($widgets->items[$id]['title'] != $title)) {
      $widgets->items[$id]['title'] = $title;
      $widgets->save();
    }
  }
  
  public function getcontent($id, $sidebar) {
    return '';
  }
  
  public static function getcachefilename($id) {
    $theme = ttheme::instance();
    if ($theme->name == '') {
      $theme = tview::instance()->theme;
    }
    return litepublisher::$paths->cache . sprintf('widget.%s.%d.php', $theme->name, $id);
  }
  
  public function expired($id) {
    switch ($this->cache) {
      case 'cache':
      $cache = twidgetscache::instance();
      $cache->expired($id);
      break;
      
      case 'include':
      $sidebar = self::findsidebar($id);
      $filename = self::getcachefilename($id, $sidebar);
      file_put_contents($filename, $this->getcontent($id, $sidebar));
      break;
    }
  }
  
  public static function findsidebar($id) {
    $view = tview::instance();
    foreach ($view->sidebars as $i=> $sidebar) {
      foreach ($sidebar as $item) {
        if ($id == $item['id']) return $i;
      }
    }
    return 0;
  }
  
  public function expire() {
    $widgets = twidgets::instance();
    foreach ($widgets->items as $id => $item) {
      if ($this instanceof $item['class']) $this->expired($id);
    }
  }
  
  public function getcontext($class) {
    if (litepublisher::$urlmap->context instanceof $class) return litepublisher::$urlmap->context;
    //ajax
    $widgets = twidgets::instance();
    return litepublisher::$urlmap->getidcontext($widgets->idurlcontext);
  }
  
}//class

class torderwidget extends twidget {
  
  protected function create() {
    parent::create();
    unset($this->id);
    $this->data['id'] = 0;
    $this->data['ajax'] = false;
    $this->data['order'] = 0;
    $this->data['sidebar'] = 0;
  }
  
  public function onsidebar(array &$items, $sidebar) {
    if ($sidebar != $this->sidebar) return;
    $order = $this->order;
    if (($order < 0) || ($order >= count($items))) $order = count($items);
    array_insert($items, array('id' => $this->id, 'ajax' => $this->ajax), $order);
  }
  
}//class

class tclasswidget extends twidget {
  private $item;
  
  private function isvalue($name) {
    return in_array($name, array('ajax', 'order', 'sidebar'));
  }
  
  public function __get($name) {
    if ($this->isvalue($name)) {
      if (!$this->item) {
        $widgets = twidgets::instance();
        $this->item = &$widgets->finditem($widgets->find($this));
      }
      return $this->item[$name];
    }
    return parent::__get($name);
  }
  
  public function __set($name, $value) {
    if ($this->isvalue($name)) {
      if (!$this->item) {
        $widgets = twidgets::instance();
        $this->item = &$widgets->finditem($widgets->find($this));
      }
      $this->item[$name] = $value;
    } else {
      parent::__set($name, $value);
    }
  }
  
  public function save() {
    parent::save();
    $widgets = twidgets::instance();
    $widgets->save();
  }
  
}//class

class twidgets extends titems_storage {
  public $classes;
  public $currentsidebar;
  public $idwidget;
  public $idurlcontext;
  
  public static function instance($id = null) {
    return getinstance(__class__);
  }
  
  protected function create() {
    $this->dbversion = false;
    parent::create();
    $this->addevents('onwidget', 'onadminlogged', 'onadminpanel', 'ongetwidgets', 'onsidebar');
    $this->basename = 'widgets';
    $this->currentsidebar = 0;
    $this->idurlcontext = 0;
    $this->addmap('classes', array());
  }
  
  public function add(twidget $widget) {
    return $this->additem( array(
    'class' => get_class($widget),
    'cache' => $widget->cache,
    'title' => $widget->gettitle(0),
    'template' => $widget->template
    ));
  }
  
  public function addext(twidget $widget, $title, $template) {
    return $this->additem( array(
    'class' => get_class($widget),
    'cache' => $widget->cache,
    'title' => $title,
    'template' => $template
    ));
  }
  
  public function addclass(twidget $widget, $class) {
    $this->lock();
    $id = $this->add($widget);
    if (!isset($this->classes[$class])) $this->classes[$class] = array();
    $this->classes[$class][] = array(
    'id' => $id,
    'order' => 0,
    'sidebar' => 0,
    'ajax' => false
    );
    $this->unlock();
    return $id;
  }
  
  public function subclass($id) {
    foreach ($this->classes as $class => $items) {
      foreach ($items as $item) {
        if ($id == $item['id']) return $class;
      }
    }
    return false;
  }
  
  public function delete($id) {
    if (!isset($this->items[$id])) return false;
    
    foreach ($this->classes as $class => $items) {
      foreach ($items as $i => $item) {
        if ($id == $item['id']) array_delete($this->classes[$class], $i);
      }
    }
    
    unset($this->items[$id]);
    $this->deleted($id);
    $this->save();
    return true;
  }
  
  public function deleteclass($class) {
    $this->unsubscribeclassname($class);
    $deleted = array();
    foreach ($this->items as $id => $item) {
      if($class == $item['class']) {
        unset($this->items[$id]);
        $deleted[] = $id;
      }
    }
    
    if (count($deleted) > 0) {
      foreach ($this->classes as $name => $items) {
        foreach ($items as $i => $item) {
          if (in_array($item['id'], $deleted)) array_delete($this->classes[$name], $i);
        }
        if (count($this->classes[$name]) == 0) unset($this->classes[$name]);
      }
    }
    
    if (isset($this->classes[$class])) unset($this->classes[$class]);
    $this->save();
    foreach ($deleted as $id)     $this->deleted($id);
  }
  
  public function getwidget($id) {
    if (!isset($this->items[$id])) return $this->error("The requested $id widget not found");
    $class = $this->items[$id]['class'];
    if (!class_exists($class)) {
      $this->delete($id);
      return $this->error("The $class class not found");
    }
    $result = getinstance($class);
    $result->id = $id;
    return $result;
  }
  
  public function getsidebar($context, tview $view) {
    return $this->getsidebarindex($context, $view, $this->currentsidebar++);
  }
  
  public function getsidebarindex($context, tview $view, $sidebar) {
    $items = $this->getwidgets($context, $view, $sidebar);
    if ($context instanceof iwidgets) $context->getwidgets($items, $sidebar);
    if (litepublisher::$options->admincookie) $this->callevent('onadminlogged', array(&$items, $sidebar));
    if (litepublisher::$urlmap->adminpanel) $this->callevent('onadminpanel', array(&$items, $sidebar));
    $this->callevent('ongetwidgets', array(&$items, $sidebar));
    $result = $this->getsidebarcontent($items, $sidebar, !$view->customsidebar && $view->disableajax);
    if ($context instanceof iwidgets) $context->getsidebar($result, $sidebar);
    $this->callevent('onsidebar', array(&$result, $sidebar));
    return $result;
  }
  
  private function getwidgets($context, tview $view, $sidebar) {
    $theme = $view->theme;
    if (($view->id >  1) && !$view->customsidebar) {
      $view = tview::instance(1);
    }
    
    $items =  isset($view->sidebars[$sidebar]) ? $view->sidebars[$sidebar] : array();
    
    $subitems =  $this->getsubitems($context, $sidebar);
    $items = $this->joinitems($items, $subitems);
    if ($sidebar + 1 == $theme->sidebarscount) {
      for ($i = $sidebar + 1; $i < count($view->sidebars); $i++) {
        $subitems =  $this->joinitems($view->sidebars[$i], $this->getsubitems($context, $i));
        
        //delete copies
        foreach ($subitems as $index => $subitem) {
          $id = $subitem['id'];
          foreach ($items as $item) {
            if ($id == $item['id']) array_delete($subitems, $index);
          }
        }
        
        foreach ($subitems as $item) $items[] = $item;
      }
    }
    
    return $items;
  }
  
  private function getsubitems($context, $sidebar) {
    $result = array();
    foreach ($this->classes as $class => $items) {
      if ($context instanceof $class) {
        foreach ($items as  $item) {
          if ($sidebar == $item['sidebar']) $result[] = $item;
        }
      }
    }
    return $result;
  }
  
  private function joinitems(array $items, array $subitems) {
    if (count($subitems) == 0) return $items;
    if (count($items) > 0) {
      //delete copies
      for ($i = count($items) -1; $i >= 0; $i--) {
        $id = $items[$i]['id'];
        foreach ($subitems as $subitem) {
          if ($id == $subitem['id']) array_delete($items, $i);
        }
      }
    }
    //join
    foreach ($subitems as $item) {
      $count = count($items);
      $order = $item['order'];
      if (($order < 0) || ($order >= $count)) {
        $items[] = $item;
      } else {
        array_insert($items, $item, $order);
      }
    }
    
    return $items;
  }
  
  private function getsidebarcontent(array $items, $sidebar, $disableajax) {
    $result = '';
    foreach ($items as $item) {
      $id = $item['id'];
      if (!isset($this->items[$id])) continue;
      $cachetype = $this->items[$id]['cache'];
      if ($disableajax)  $item['ajax'] = false;
      if ($item['ajax'] === 'inline') {
        switch ($cachetype) {
          case 'cache':
          case 'nocache':
          case false:
          $content = $this->getinline($id, $sidebar);
          break;
          
          default:
          $content = $this->getajax($id, $sidebar);
          break;
        }
      } elseif ($item['ajax']) {
        $content = $this->getajax($id, $sidebar);
      } else {
        switch ($cachetype) {
          case 'cache':
          $content = $this->getwidgetcache($id, $sidebar);
          break;
          
          case 'include':
          $content = $this->includewidget($id, $sidebar);
          break;
          
          case 'nocache':
          case false:
          $widget = $this->getwidget($id);
          $content = $widget->getwidget($id, $sidebar);
          break;
          
          case 'code':
          $content = $this->getcode($id, $sidebar);
          break;
        }
      }
      $this->callevent('onwidget', array($id, &$content));
      $result .= $content;
    }
    return $result;
  }
  
  public function getajax($id, $sidebar) {
    $title = sprintf('<a onclick="widget_load(this, %d, %d)">%s</a>', $id, $sidebar, $this->items[$id]['title']);
    $content = "<!--widgetcontent-$id-->";
    $theme = ttheme::instance();
    return $theme->getwidget($title, $content, $this->items[$id]['template'], $sidebar);
  }
  
  public function getinline($id, $sidebar) {
    $title = sprintf('<a rel="inlinewidget" href="">%s</a>', $this->items[$id]['title']);
    if ('cache' == $this->items[$id]['cache']) {
      $cache = twidgetscache::instance();
      $content = $cache->getcontent($id, $sidebar);
    } else {
      $widget = $this->getwidget($id);
      $content = $widget->getcontent($id, $sidebar);
    }
    $content = sprintf('<!--%s-->', $content);
    $theme = ttheme::instance();
    return $theme->getwidget($title, $content, $this->items[$id]['template'], $sidebar);
  }
  
  public function getwidgetcache($id, $sidebar) {
    $title = $this->items[$id]['title'];
    $cache = twidgetscache::instance();
    $content = $cache->getcontent($id, $sidebar);
    $theme = ttheme::instance();
    return $theme->getwidget($title, $content, $this->items[$id]['template'], $sidebar);
  }
  
  private function includewidget($id, $sidebar) {
    $filename = twidget::getcachefilename($id, $sidebar);
    if (!file_exists($filename)) {
      $widget = $this->getwidget($id);
      $content = $widget->getcontent($id, $sidebar);
      file_put_contents($filename, $content);
      @chmod($filename, 0666);
    }
    
    $theme = ttheme::instance();
    return $theme->getwidget($this->items[$id]['title'], "\n<?php @include('$filename'); ?>\n", $this->items[
    $id]['template'], $sidebar);
  }
  
  private function getcode($id, $sidebar) {
    $class = $this->items[$id]['class'];
    return "\n<?php
    \$widget = $class::instance();
    \$widget->id = \$id;
    echo \$widget->getwidget($id, $sidebar);
    ?>\n";
  }
  
  public function find(twidget $widget) {
    $class = get_class($widget);
    foreach ($this->items as $id => $item) {
      if ($class == $item['class']) return $id;
    }
    return false;
  }
  
  public function xmlrpcgetwidget($id, $sidebar, $idurl) {
    if (!isset($this->items[$id])) return $this->error("Widget $id not found");
    $this->idurlcontext = $idurl;
    $result = $this->getwidgetcontent($id, $sidebar);
    //fix bug for javascript client library
    if ($result == '') return 'false';
  }
  
  private static function getget($name) {
    return isset($_GET[$name]) ? (int) $_GET[$name] : false;
  }
  
  private static function error_request($s) {
    return '<?php header(\'HTTP/1.1 400 Bad Request\', true, 400); ?>' . turlmap::htmlheader(false) . $s;
  }
  
  public function request($arg) {
    $this->cache = false;
    $id = self::getget('id');
    $sidebar = self::getget('sidebar');
    $this->idurlcontext = self::getget('idurl');
    if (($id === false) || ($sidebar === false) || !$this->itemexists($id)) return $this->error_request('Invalid params');
    $themename = isset($_GET['themename']) ? trim($_GET['themename']) : tview::instance(1)->themename;
    if (!preg_match('/^\w[\w\.\-_]*+$/', $themename) || !ttheme::exists($themename)) $themename = tviews::instance(1)->themename;
    $theme = ttheme::getinstance($themename);
    
    try {
      $result = $this->getwidgetcontent($id, $sidebar);
      return turlmap::htmlheader(false) . $result;
    } catch (Exception $e) {
      return $this->error_request('Cant get widget content');
    }
  }
  
  public function getwidgetcontent($id, $sidebar) {
    if (!isset($this->items[$id])) return false;
    switch ($this->items[$id]['cache']) {
      case 'cache':
      $cache = twidgetscache::instance();
      $result = $cache->getcontent($id, $sidebar);
      break;
      
      case 'include':
      $filename = twidget::getcachefilename($id, $sidebar);
      if (file_exists($filename)) {
        $result = file_get_contents($filename);
      } else {
        $widget = $this->getwidget($id);
        $result = $widget->getcontent($id, $sidebar);
        file_put_contents($filename, $result);
        @chmod($filename, 0666);
      }
      break;
      
      case 'nocache':
      case 'code':
      case false:
      $widget = $this->getwidget($id);
      $result = $widget->getcontent($id, $sidebar);
      break;
    }
    
    return $result;
  }
  
  public function getpos($id) {
    return tsidebars::getpos($this->sidebars, $id);
  }
  
  public function &finditem($id) {
    foreach ($this->classes as $class => $items) {
      foreach ($items as $i => $item) {
        if ($id == $item['id']) return $this->classes[$class][$i];
      }
    }
    $item = null;
    return $item;
  }
  
}//class

class twidgetscache extends titems {
  private $modified;
  
  public static function instance($id = null) {
    return getinstance(__class__);
  }
  
  protected function create() {
    $this->dbversion = false;
    parent::create();
    $this->modified = false;
  }
  
  public function getbasename() {
    $theme = ttheme::instance();
    return 'widgetscache.' . $theme->name;
  }
  
  public function load() {
    if ($s = tfilestorage::loadfile(litepublisher::$paths->cache . $this->getbasename() .'.php')) {
      return $this->loadfromstring($s);
    }
    return false;
  }
  
  public function savemodified() {
    if ($this->modified) {
      tfilestorage::savetofile(litepublisher::$paths->cache .$this->getbasename(),
      $this->savetostring());
    }
    $this->modified = false;
  }
  
  public function save() {
    if (!$this->modified) {
      litepublisher::$urlmap->onclose['widgetscache'] = array($this, 'savemodified');
      $this->modified = true;
    }
  }
  
  public function getcontent($id, $sidebar) {
    if (isset($this->items[$id][$sidebar])) return $this->items[$id][$sidebar];
    return $this->setcontent($id, $sidebar);
  }
  
  public function setcontent($id, $sidebar) {
    $widgets = twidgets::instance();
    $widget = $widgets->getwidget($id);
    $result = $widget->getcontent($id, $sidebar);
    $this->items[$id][$sidebar] = $result;
    $this->save();
    return $result;
  }
  
  public function expired($id) {
    if (isset($this->items[$id])) {
      unset($this->items[$id]);
      $this->save();
    }
  }
  
  public function onclearcache() {
    $this->items = array();
    $this->modified = false;
  }
  
}//class

?>