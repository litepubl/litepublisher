<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tmenuwidget extends tclasswidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->cache = 'nocache';
    $this->basename = 'widget.menu';
    $this->template = 'submenu';
    $this->adminclass = 'tadminorderwidget';
  }
  
  public function getdeftitle() {
    return tlocal::$data['default']['submenu'];
  }
  
  public function getwidget($id, $sitebar) {
    $template = ttemplate::instance();
    if ($template->hover) return '';
    return parent::getwidget($id, $sitebar);
  }
  
  public function gettitle($id) {
    if (litepublisher::$urlmap->context instanceof tmenu) return litepublisher::$urlmap->context->title;
    return parent::gettitle($id);
  }
  
  public function getcontent($idwidget, $sitebar) {
    $menu = $this->getcontext('tmenu');
    $id = $menu->id;
    $menus = $menu->owner;
    $result = '';
    $theme = ttheme::instance();
    $tml = $theme->getwidgetitem('submenu', $sitebar);
    // 1 submenu list
    $submenu = '';
    $childs = $menus->getchilds($id);
    foreach ($childs as $child) {
      $submenu .= $this->getitem($tml, $menus->getitem($child), '');
    }
    
    $parent = $menus->getparent($id);
    if ($parent == 0) {
      $result = $submenu;
    } else {
      $sibling = $menus->getchilds($parent);
      foreach ($sibling as $iditem) {
        $result .= $this->getitem($tml, $menus->getitem($iditem), $iditem == $id ? $submenu : '');
      }
    }
    
    $parents = $menus->getparents($id);
    foreach ($parents as $parent) {
      $result = $this->getitem($tml, $menus->getitem($parent), $result);
    }
    
    if ($result == '')  return '';
    return $theme->getwidgetcontent($result, 'submenu', $sitebar);
  }
  
  private function getitem($tml, $item, $subnodes) {
    $args = targs::instance();
    $args->add($item);
    $args->anchor = $item['title'];
    $args->rel = 'menu';
    $args->icon = '';
    $args->subitems = $subnodes == '' ? '' : sprintf('<ul>%s</ul>', $subnodes);
    $theme = ttheme::instance();
    return $theme->parsearg($tml, $args);
  }
  
}//class

?>