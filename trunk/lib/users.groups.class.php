<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tusergroups extends titems {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'usergroups';
    $this->data['defaultgroup'] = 'nobody';
    $this->addevents('onhasright');
  }
  
  function add($name, $home = '/ADMIN/') {
    if ($id = $this->groupid($name)) return $id;
    $this->items[++$this->autoid] = array(
    'name' => $name,
    'home' => $home
    );
    $this->save();
    return $this->autoid;
  }
  
  public function groupid($name) {
    foreach ($this->items as $id => $item) {
      if ($name == $item['name']) return $id;
    }
    return false;
  }
  
  public function hasright($who, $group) {
    if ($who == $group) return  true;
    if (($who == 'admin') || ($group == 'nobody')) return true;
    switch ($who) {
      case 'editor':
      if ($group == 'author') return true;
      break;
      
      case 'moderator':
      if (($group == 'subscriber') || ($group == 'author')) return true;
      break;
      
      case 'subeditor':
      if (in_array($group, array('author', 'subscriber', 'moderator'))) return true;
      break;
    }
    
    if ($this->onhasright($who, $group)) return true;
    return false;
  }
  
  public function gethome($name) {
    if ($id = $this->groupid($name)) {
      return isset($this->items[$id]['home']) ? $this->items[$id]['home'] : '/admin/';
    }
    return '/admin/';
  }
  
}//class
