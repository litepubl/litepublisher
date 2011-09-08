<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tmarkdownplugin extends tplugin {
  public $parser;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->data['deletep'] = true;
    
    require_once(dirname(__file__) . DIRECTORY_SEPARATOR . 'markdown.parser.class.php');
    $this->parser = new Markdown_Parser();
  }
  
  public function filter(&$content) {
    if ($this->deletep) $content = str_replace('_', '&#95;', $content);
    $content = $this->parser->transform($content);
    if ($this->deletep) $content = strtr($content, array(
    '<p>' => '',
    '</p>' => '',
    '&#95;' => '_'
    ));
  }
  
  public function install() {
    $filter = tcontentfilter::instance();
    $filter->lock();
    $filter->onsimplefilter = $this->filter;
    $filter->oncomment = $this->filter;
    $filter->unlock();
  }
  
  public function uninstall() {
    $filter = tcontentfilter::instance();
    $filter->unsubscribeclass($this);
  }
  
}//class