<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tsimplecontent  extends tevents_itemplate implements itemplate {
  public $text;
  public $html;

  public static function instance() {
    return Getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'simplecontent';
  
  public function  httpheader() {
    return turlmap::htmlheader(false);
  }
  
public function request($arg) {}
public function gettitle() {}

  public function getcont() {
    $result = empty($this->text) ? $this->html : sprintf("<h2>%s</h2>\n", $this->text);
    $theme =tview::getview(1)->theme;
    return $theme->simple($result);
  }
  
  public static function html($content) {
    $class = __class__;
    $self = new $class();
    $self->html = $content;
    $template = ttemplate::instance();
    return $template->request($self);
  }
  
  public static function content($content) {
    $class = __class__;
    $self = new $class();
    $self->text = $content;
    $template = ttemplate::instance();
    return $template->request($self);
  }
  
}//class

?>