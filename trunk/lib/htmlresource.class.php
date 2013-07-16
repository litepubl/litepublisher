<?php
/**
* Lite Publisher
* Copyright (C) 2010 - 2013 Vladimir Yushko http://litepublisher.ru/ http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class thtmltag {
  public $tag;
  
public function __construct($tag) { $this->tag = $tag; }
  public function __get($name) {
    return sprintf('<%1$s>%2$s</%1$s>', $this->tag, tlocal::i()->$name);
  }
  
}//class

class tadminhtml {
  public static $tags = array('h1', 'h2', 'h3', 'h4', 'p', 'li', 'ul', 'strong');
  public $section;
  public $searchsect;
  public $ini;
  private $map;
  private $section_stack;
  
  public static function i() {
    $self = getinstance(__class__);
    if (count($self->ini) == 0) $self->load();
    return $self;
  }
  
  public static function getinstance($section) {
    $self = self::i();
    $self->section = $section;
    tlocal::i($section);
    return $self;
  }
  
  public function __construct() {
    $this->ini = array();
    $this->searchsect = array('common');
    tlocal::usefile('admin');
  }
  
  public function __get($name) {
    if (isset($this->ini[$this->section][$name])) return $this->ini[$this->section][$name];
    foreach ($this->searchsect as $section) {
      if (isset($this->ini[$section][$name])) return $this->ini[$section][$name];
    }
    
    if (in_array($name, self::$tags)) return new thtmltag($name);
    throw new Exception("the requested $name item not found in $this->section section");
  }
  
  public function __call($name, $params) {
    $s = $this->__get($name);
    if (is_object($s) && ($s instanceof thtmltag))  return sprintf('<%1$s>%2$s</%1$s>', $name, $params[0]);
    
    $args = isset($params[0]) && $params[0] instanceof targs ? $params[0] : new targs();
    return $this->parsearg($s, $args);
  }
  
  public function parsearg($s, targs $args) {
    if (!is_string($s)) $s = (string) $s;
    $theme = ttheme::i();
    // parse tags [form] .. [/form]
    if (is_int($i = strpos($s, '[form]'))) {
    $form = $theme->templates['content.admin.form'];
      $replace = substr($form, 0, strpos($form, '$items'));
      $s = substr_replace($s, $replace, $i, strlen('[form]'));
    }
    
    if ($i = strpos($s, '[/form]')) {
      $replace = substr($form, strrpos($form, '$items') + strlen('$items'));
      $s = substr_replace($s, $replace, $i, strlen('[/form]'));
    }
    
    if (preg_match_all('/\[(editor|checkbox|text|password|combo|hidden|calendar)(:|=)(\w*+)\]/i', $s, $m, PREG_SET_ORDER)) {
      foreach ($m as $item) {
        $type = $item[1];
        $name = $item[3];
        $varname = '$' . $name;
        //convert spec charsfor editor
        if (!(($type == 'checkbox') || ($type == 'combo') || ($type == 'calendar'))) {
          if (isset($args->data[$varname])) {
            $args->data[$varname] = self::specchars($args->data[$varname]);
          } else {
            $args->data[$varname] = '';
          }
        }
        
        if ($type == 'calendar') {
          $tag = $this->getcalendar($name, $varname);
        } else {
          $tag = strtr($theme->templates["content.admin.$type"], array(
          '$name' => $name,
          '$value' => $varname
          ));
        }
        
        $s = str_replace($item[0], $tag, $s);
      }
    }
    
    $s = strtr($s, $args->data);
    return $theme->parse($s);
  }
  
  public function addsearch() {
    $a = func_get_args();
    foreach ($a as $sect) {
      if (!in_array($sect, $this->searchsect)) $this->searchsect[] = $sect;
    }
  }
  
  public function push_section($section) {
    if (!isset($this->section_stack)) $this->section_stack = array();
    $lang = tlocal::i();
    $this->section_stack[] = array(
    $this->section,
    $lang->section
    );
    
    $this->section = $section;
    $lang->section = $section;
  }
  
  public function pop_section() {
    $a = array_pop($this->section_stack);
    $this->section = $a[0];
    tlocal::i()->section = $a[1];
  }
  
  public static function specchars($s) {
    return strtr(            htmlspecialchars($s), array(
    '"' => '&quot;',
    "'" =>'&#39;',
    '$' => '&#36;',
    '%' => '&#37;',
    '_' => '&#95;'
    ));
  }
  
  public function fixquote($s) {
    $s = str_replace("\\'", '\"', $s);
    $s = str_replace("'", '"', $s);
    return str_replace('\"', "'", $s);
  }
  
  public function load() {
    $filename = tlocal::getcachedir() . 'adminhtml';
    if (tfilestorage::loadvar($filename, $v) && is_array($v)) {
      $this->ini = $v + $this->ini;
    } else {
      $merger = tlocalmerger::i();
      $merger->parsehtml();
    }
  }
  
  public function loadinstall() {
    if (isset($this->ini['installation'])) return;
    tlocal::usefile('install');
    if( $v = parse_ini_file(litepublisher::$paths->languages . 'install.ini', true)) {
      $this->ini = $v + $this->ini;
    }
  }
  
  public static function getparam($name, $default) {
    return !empty($_GET[$name]) ? $_GET[$name] : (!empty($_POST[$name]) ? $_POST[$name] : $default);
  }
  
  public static function idparam() {
    return (int) self::getparam('id', 0);
  }
  
  public static function getadminlink($path, $params) {
    return litepublisher::$site->url . $path . litepublisher::$site->q . $params;
  }
  
  public static function array2combo(array $items, $selected) {
    $result = '';
    foreach ($items as $i => $title) {
      $result .= sprintf('<option value="%s" %s>%s</option>', $i, $i == $selected ? 'selected' : '', self::specchars($title));
    }
    return $result;
  }
  
  public static function getcombobox($name, array $items, $selected) {
    return sprintf('<select name="%1$s" id="%1$s">%2$s</select>', $name,
    self::array2combo($items, $selected));
  }
  
  public function adminform($tml, targs $args) {
    $args->items = $this->parsearg($tml, $args);
    return $this->parsearg(ttheme::i()->templates['content.admin.form'], $args);
  }
  
  public function getcheckbox($name, $value) {
    return $this->getinput('checkbox', $name, $value ? 'checked="checked"' : '', '$lang.' . $name);
  }
  
  public function getradioitems($name, array $items, $selected) {
    $result = '';
    $theme = ttheme::i();
    $tml = $theme->templates['content.admin.radioitems'];
    foreach ($items as $index => $value) {
      $result .= strtr($tml, array(
      '$index' => $index,
      '$checked' => $value == $selected ? 'checked="checked"' : '',
      '$name' => $name,
      '$value' => self::specchars($value)
      ));
    }
    return $result;
  }
  
  public function getinput($type, $name, $value, $title) {
    $theme = ttheme::i();
    return strtr($theme->templates['content.admin.' . $type], array(
    '$lang.$name' => $title,
    '$name' => $name,
    '$value' => $value
    ));
  }
  
  public function getsubmit($name) {
    return strtr(ttheme::i()->templates['content.admin.submit'], array(
    '$lang.$name' => tlocal::i()->$name,
    '$name' => $name,
    ));
  }
  
  public function getedit($name, $value, $title) {
    return $this->getinput('text', $name, $value, $title);
  }
  
  public function getcombo($name, $value, $title) {
    return $this->getinput('combo', $name, $value, $title);
  }
  
  public function getcalendar($name, $date) {
    if (is_numeric($date)) {
      $date = intval($date);
    } else if ($date == '0000-00-00 00:00:00') {
      $date = 0;
    } elseif ($date == '0000-00-00') {
      $date = 0;
    } elseif (trim($date)) {
      $date = strtotime($date);
    } else {
      $date = 0;
    }
    
    return strtr($this->ini['common']['calendar'], array(
    '$title' => tlocal::i()->__get($name),
    '$name' => $name,
    '$date' => $date? date('d.m.Y', $date) : '',
    '$time' => $date ?date('H:i', $date) : '',
    ));
  }
  
  public static function getdatetime($name) {
    if (!empty($_POST[$name]) && @sscanf(trim($_POST[$name]), '%d.%d.%d', $d, $m, $y)) {
      $h = 0;
      $min  = 0;
      if (!empty($_POST[$name . '-time'])) @sscanf(trim($_POST[$name . '-time']), '%d:%d', $h, $min);
      return mktime($h,$min,0, $m, $d, $y);
    }
    
    return 0;
  }
  
  public function gettable($head, $body) {
    return strtr($this->ini['common']['table'], array(
    '$tablehead' => $head,
    '$tablebody' => $body));
  }
  
  public function tablestruct(array $tablestruct) {
    $head = '';
    $tml = '<tr>';
    foreach ($tablestruct as $elem) {
      if (!$elem || !count($elem)) continue;
      $head .= sprintf('<th align="%s">%s</th>', $elem[0], $elem[1]);
      $tml .= sprintf('<td align="%s">%s</td>', $elem[0], $elem[2]);
    }
    $tml .= '</tr>';
    
    return array($head, $tml);
  }
  
  public function buildtable(array $items, array $tablestruct) {
    $body = '';
    list($head, $tml) = $this->tablestruct($tablestruct);
    $theme = ttheme::i();
    $args = new targs();
    foreach ($items as $id => $item) {
      ttheme::$vars['item'] = $item;
      $args->add($item);
      if (!isset($item['id'])) $args->id = $id;
      $body .= $theme->parsearg($tml, $args);
    }
    unset(ttheme::$vars['item']);
    $args->tablehead  = $head;
    $args->tablebody = $body;
    return $theme->parsearg($this->ini['common']['table'], $args);
  }
  
  public function items2table($owner, array $items, array $struct) {
    $head = '';
    $body = '';
    $tml = '<tr>';
    foreach ($struct as $elem) {
      $head .= sprintf('<th align="%s">%s</th>', $elem[0], $elem[1]);
      $tml .= sprintf('<td align="%s">%s</td>', $elem[0], $elem[2]);
    }
    $tml .= '</tr>';
    
    $theme = ttheme::i();
    $args = new targs();
    foreach ($items as $id) {
      $item = $owner->getitem($id);
      $args->add($item);
      $args->id = $id;
      $body .= $theme->parsearg($tml, $args);
    }
    $args->tablehead  = $head;
    $args->tablebody = $body;
    return $theme->parsearg($this->ini['common']['table'], $args);
  }
  
  public function tableposts(array $items, array $struct) {
    $body = '';
    $head = '<th align="center"><input type="checkbox" name="invertcheck" class="invertcheck" /></th>';
    $tml = '<tr><td align="center"><input type="checkbox" name="checkbox-$post.id" id="checkbox-$post.id" value="$post.id"/>$post.id</td>';
    foreach ($struct as $elem) {
      $head .= sprintf('<th align="%s">%s</th>', $elem[0], $elem[1]);
      $tml .= sprintf('<td align="%s">%s</td>', $elem[0], $elem[2]);
    }
    $tml .= '</tr>';
    
    $theme = ttheme::i();
    $args = new targs();
    foreach ($items as $id) {
      $post = tpost::i($id);
      ttheme::$vars['post'] = $post;
      $args->id = $id;
      $body .= $theme->parsearg($tml, $args);
    }
    
    $args->tablehead  = $head;
    $args->tablebody = $body;
    return $theme->parsearg($this->ini['common']['table'], $args);
  }
  
  public function getitemscount($from, $to, $count) {
    return sprintf($this->h4->itemscount, $from, $to, $count);
  }
  
  public function get_table_checkbox($name) {
    return array('center', $this->invertcheckbox, str_replace('$checkboxname', $name, $this->checkbox));
  }
  
  public function get_table_item($name) {
    return array('left', tlocal::i()->$name, "\$$name");
  }
  
  public function get_table_link($action, $adminurl) {
    return array('left', tlocal::i()->$action, strtr($this->actionlink , array(
    '$action' => $action,
    '$lang.action' => tlocal::i()->$action,
    '$adminurl' => $adminurl
    )));
  }
  
  public function tableprops($item) {
    $body = '';
    $lang = tlocal::i();
    foreach ($item as $k => $v) {
      if (($k === false) || ($v === false)) continue;
      $body .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $lang->__get($k), $v);
    }
    
    return $this->gettable("<th>$lang->name</th> <th>$lang->value</th>", $body);
  }
  
  public function tablevalues(array $a) {
    $body = '';
    foreach ($a as $k => $v) {
      $body .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $k, $v);
    }
    
    $lang = tlocal::i();
    return $this->gettable("<th>$lang->name</th> <th>$lang->value</th>", $body);
  }
  
  public function singlerow(array $a) {
    $head = '';
    $body = '<tr>';
    foreach ($a as $k => $v) {
      $head .= sprintf('<th>%s</th>', $k);
      $body .= sprintf('<td>%s</td>', $v);
    }
    $body .= '</tr>';
    
    return $this->gettable($head, $body);
  }
  
  public function confirmdelete($id, $adminurl, $mesg) {
    $args = targs::i();
    $args->id = $id;
    $args->action = 'delete';
    $args->adminurl = $adminurl;
    $args->confirm = $mesg;
    return $this->confirmform($args);
  }
  
  public function confirm_delete($owner, $adminurl) {
    $id = (int) self::getparam('id', 0);
    if (!$owner->itemexists($id)) return $this->h4->notfound;
    if  (isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 1)) {
      $owner->delete($id);
      return $this->h4->successdeleted;
    } else {
      $args = new targs();
      $args->id = $id;
      $args->adminurl = $adminurl;
      $args->action = 'delete';
      $args->confirm = tlocal::i()->confirmdelete;
      return $this->confirmform($args);
    }
  }
  
  public static function check2array($prefix) {
    $result = array();
    foreach ($_POST as $key => $value) {
      if (strbegin($key, $prefix)) {
        $result[] = is_numeric($value) ? (int) $value : $value;
      }
    }
    return $result;
  }
  
  public function inidir($dir) {
$filename = $dir . 'html.ini';
if (!isset(ttheme::$inifiles[$filename])) {
    $html_ini = ttheme::cacheini($filename);
    if (is_array($html_ini)) {
      $this->ini = $html_ini + $this->ini;
      $keys = array_keys($html_ini);
      $this->section = array_shift($keys);
$this->searchsect[] = $this->section;
    }
}
    
$filename = $dir . litepublisher::$options->language . '.admin.ini';
if (!isset(ttheme::$inifiles[$filename])) {
    $lang_ini = ttheme::cacheini($filename);
    if (is_array($lang_ini)) {
      $lang = tlocal::i();
      $lang->ini = $lang_ini + $lang->ini ;
      $keys = array_keys($lang_ini);
      $lang->section = array_shift($keys);
$lang->addsearch($lang->section);
    }
}
    
    return $this;
  }
  
  public function iniplugin($class) {
    return $this->inidir(litepublisher::$classes->getresourcedir($class));
  }
  
}//class

class tautoform {
  const editor = 'editor';
  const text = 'text';
  const checkbox = 'checkbox';
  const hidden = 'hidden';
  
  public $obj;
  public $props;
  public $section;
  public $_title;
  
  public static function i() {
    return getinstance(__class__);
  }
  
  public function __construct(tdata $obj, $section, $titleindex) {
    $this->obj = $obj;
    $this->section = $section;
    $this->props = array();
    $lang = tlocal::i($section);
    $this->_title = $lang->$titleindex;
  }
  
  public function __set($name, $value) {
    $this->props[] = array(
    'obj' => $this->obj,
    'propname' => $name,
    'type' => $value
    );
  }
  
  public function __get($name) {
    if (isset($this->obj->$name)) {
      return array(
      'obj' => $this->obj,
      'propname' => $name
      );
    }
    //tlogsubsystem::error(sprintf('The property %s not found in class %s', $name, get_class($this->obj));
  }
  
  public function __call($name, $args) {
    if (isset($this->obj->$name)) {
      $result = array(
      'obj' => $this->obj,
      'propname' => $name,
      'type' => $args[0]
      );
      if (($result['type'] == 'combo') && isset($args[1]))  $result['items'] = $args[1];
      return $result;
    }
  }
  
  public function add() {
    $a = func_get_args();
    foreach ($a as $prop) {
      $this->addprop($prop);
    }
  }
  
  public function addsingle($obj, $propname, $type) {
    return $this->addprop(array(
    'obj' => $obj,
    'propname' => $propname,
    'type' => $type
    ));
  }
  
  public function addeditor($obj, $propname) {
    return $this->addsingle($obj, $propname, 'editor');
  }
  
  public function addprop(array $prop) {
    if (isset($prop['type'])) {
      $type = $prop['type'];
    } else {
      $type = 'text';
    $value = $prop['obj']->{$prop['propname']};
      if (is_bool($value)) {
        $type = 'checkbox';
      } elseif(strpos($value, "\n")) {
        $type = 'editor';
      }
    }
    
    $item = array(
    'obj' => $prop['obj'],
    'propname' => $prop['propname'],
    'type' => $type,
    'title' => isset($prop['title']) ? $prop['title'] : ''
    );
    if (($type == 'combo') && isset($prop['items'])) $item['items'] = $prop['items'];
    $this->props[] = $item;
    return count($this->props) - 1;
  }
  
  public function getcontent() {
    $result = '';
    $lang = tlocal::i();
    $theme = ttheme::i();
    
    foreach ($this->props as $prop) {
    $value = $prop['obj']->{$prop['propname']};
      switch ($prop['type']) {
        case 'text':
        case 'editor':
        $value = tadminhtml::specchars($value);
        break;
        
        case 'checkbox':
        $value = $value ? 'checked="checked"' : '';
        break;
        
        case 'combo':
        $value = tadminhtml  ::array2combo($prop['items'], $value);
        break;
      }
      
      $result .= strtr($theme->templates['content.admin.' . $prop['type']], array(
    '$lang.$name' => empty($prop['title']) ? $lang->{$prop['propname']} : $prop['title'],
      '$name' => $prop['propname'],
      '$value' => $value
      ));
    }
    return $result;
  }
  
  public function getform() {
    $args = targs::i();
    $args->formtitle = $this->_title;
    $args->items = $this->getcontent();
    $theme = ttheme::i();
    return $theme->parsearg($theme->templates['content.admin.form'], $args);
  }
  
  public function processform() {
    foreach ($this->props as $prop) {
      if (method_exists($prop['obj'], 'lock')) $prop['obj']->lock();
    }
    
    foreach ($this->props as $prop) {
      $name = $prop['propname'];
      if (isset($_POST[$name])) {
        $value = trim($_POST[$name]);
        if ($prop['type'] == 'checkbox') $value = true;
      } else {
        $value = false;
      }
      $prop['obj']->$name = $value;
    }
    
    foreach ($this->props as $prop) {
      if (method_exists($prop['obj'], 'unlock')) $prop['obj']->unlock();
    }
  }
  
}//class

class ttablecolumns {
  public $style;
  public $head;
  public $checkboxes;
  public $checkbox_tml;
  public $item;
  public $changed_hidden;
  public $index;
  
  public function __construct() {
    $this->index = 0;
    $this->style = '';
    $this->checkboxes = array();
    $this->checkbox_tml = '<input type="checkbox" name="checkbox-showcolumn-%1$d" value="%1$d" %2$s />
    <label for="checkbox-showcolumn-%1$d"><strong>%3$s</strong></label>';
    $this->head = '';
    $this->body = '';
    $this->changed_hidden = 'changed_hidden';
  }
  
  public function addcolumns(array $columns) {
    foreach ($columns as $column) {
      list($tml, $title, $align, $show) = $column;
      $this->add($tml, $title, $align, $show);
    }
  }
  
  public function add($tml, $title, $align, $show) {
    $class = 'col_' . ++$this->index;
    //if (isset($_POST[$this->changed_hidden])) $show  = isset($_POST["checkbox-showcolumn-$this->index"]);
    $display = $show ? 'block' : 'none';
  $this->style .= ".$class { text-align: $align; display: $display; }\n";
    $this->checkboxes[]=  sprintf($this->checkbox_tml, $this->index, $show ? 'checked="checked"' : '', $title);
    $this->head .= sprintf('<th class="%s">%s</th>', $class, $title);
    $this->body .= sprintf('<td class="%s">%s</td>', $class, $tml);
    return $this->index;
  }
  
  public function build($body, $buttons) {
    $args = targs::i();
    $args->style = $this->style;
    $args->checkboxes = implode("\n", $this->checkboxes);
    $args->head = $this->head;
    $args->body = $body;
    $args->buttons = $buttons;
    $tml = file_get_contents(litepublisher::$paths->languages . 'tablecolumns.ini');
    $theme = ttheme::i();
    return $theme->parsearg($tml, $args);
  }
  
}//class

class tuitabs {
  public $head;
  public $body;
  public $tabs;
  private static $index = 0;
  private $tabindex;
  private $items;
  
  public function __construct() {
    $this->tabindex = ++self::$index;
    $this->items = array();
    $this->head = '<li><a href="%s"><span>%s</span></a></li>';
    $this->body = '<div id="tab-' . self::$index . '-%d">%s</div>';
    $this->tabs = '<div id="tabs-' . self::$index . '" class="admintabs">
    <ul>%s</ul>
    %s
    </div>';
  }
  
  public function get() {
    $head= '';
    $body = '';
    foreach ($this->items as $i => $item) {
      if (isset($item['url'])) {
        $head .= sprintf($this->head, $item['url'], $item['title']);
      } else {
        $head .= sprintf($this->head, "#tab-$this->tabindex-$i", $item['title']);
        $body .= sprintf($this->body, $i, $item['body']);
      }
    }
    return sprintf($this->tabs, $head, $body);
  }
  
  public function add($title, $body) {
    $this->items[] = array(
    'title' => $title,
    'body' => $body
    );
  }
  
  public function ajax($title, $url) {
    $this->items[] = array(
    'url' => $url,
    'title' => $title,
    );
  }
  
  public static function gethead() {
  return ttemplate::i()->getready('$($("div.admintabs").get().reverse()).tabs({ beforeLoad: litepubl.uibefore})');
  }
  
}//class