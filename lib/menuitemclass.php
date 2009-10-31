<?php

class tmenuitem extends TItem implements  ITemplate {
const ownerprops = array('title', 'url', 'idur', 'parent', 'order');l
  
  public function getbasename() {
    return 'menus' . DIRECTORY_SEPARATOR . $this->id;
  }
  
  public static function instance($id = 0) {
    return parent::instance(__class__, $id);
  }
  
  protected function create() {
    parent::create();
    $this->data= array(
'id' => 0,
    'author' => 0, //not supported
    'content' => '',
    'rawcontent' => '',
    'keywords' => '',
    'description' => '',
    'password' => '',
    'template' => '',
    'theme' => '',
    );
  }

public function __get($name) {
if (in_array($name, self::ownerprops))return $this->owner->items[$id][$name];
return parent::__get($name);
}

public function __set($name, $value) {
if (in_array($name, self::ownerprops))return $this->owner->setvalue($this->id, $name, $value);
parent::__set($name, $value);
}
  

public function getowner() {
return tmenu::instance();
  //ITemplate
//public function request($id) {}
public function gethead() {}
  
  public function gettitle() {
    return $this->data['title'];
  }
  
  public function getkeywords() {
    return $this->data['keywords'];
  }
  
  public function getdescription() {
    return $this->data['description'];
  }
  
  public function GetTemplateContent() {
        $GLOBALS['post'] = &$this;
$theme = ttheme::instance();
    return $theme->parse($theme->menucontent);
  }
  
  public function &Getsubmenu() {
    global $Options;
    $result = array();
    $menu = TMenu::instance();
    $Childs = $menu->items[$this->id]['childs'];
    if (count($Childs) > 0) {
      $Items = &$menu->items;
      foreach ($Childs as $id) {
        $result[] = array(
        'url' =>       $Options->url . $Items[$id]['url'],
        'title' =>  $Items[$id]['title'],
        'subitems' => array()
        );
      }
    }
    return $result;
  }
  
}

?>