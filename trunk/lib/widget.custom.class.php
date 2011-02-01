<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcustomwidget extends twidget {
  public $items;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename   = 'widgets.custom';
    $this->adminclass = 'tadmincustomwidget';
    $this->addmap('items', array());
    $this->addevents('added', 'deleted');
  }
  
  public function getwidget($id, $sidebar) {
    if (!isset($this->items[$id])) return '';
    $item = $this->items[$id];
    if ($item['template'] == '') return $item['content'];
    $theme = ttheme::instance();
    return $theme->getwidget($item['title'], $item['content'], $item['template'], $sidebar);
  }
  
  public function gettitle($id) {
    return $this->items[$id]['title'];
  }
  
  public function getcontent($id, $sidebar) {
    return $this->items[$id]['content'];
  }
  
  public function add($title, $content, $template) {
    $widgets = twidgets::instance();
    $widgets->lock();
    $id = $widgets->addext($this, $title, $template);
    $this->items[$id] = array(
    'title' => $title,
    'content' => $content,
    'template' => $template
    );
    
    $sidebars = tsidebars::instance();
    $sidebars->add($id);
    $widgets->unlock();
    $this->save();
    $this->added($id);
    return $id;
  }
  
  public function edit($id, $title, $content, $template) {
    $this->items[$id] = array(
    'title' => $title,
    'content' => $content,
    'template' => $template
    );
    $this->save();
    
    $widgets = twidgets::instance();
    $widgets->items[$id]['title'] = $title;
    $widgets->save();
    $this->expired($id);
    litepublisher::$urlmap->clearcache();
  }
  
  public function delete($id) {
    if (isset($this->items[$id])) {
      unset($this->items[$id]);
      $this->save();
      
      $widgets = twidgets::instance();
      $widgets->delete($id);
      $this->deleted($id);
    }
  }
  
  public function widgetdeleted($id) {
    if (isset($this->items[$id])) {
      unset($this->items[$id]);
      $this->save();
    }
  }
  
} //class
?>