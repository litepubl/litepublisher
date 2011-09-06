<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcommentswidget extends twidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'widget.comments';
    $this->cache = 'include';
    $this->template = 'comments';
    $this->adminclass = 'tadminmaxcount';
    $this->data['maxcount'] =  7;
  }
  
  public function getdeftitle() {
    return tlocal::get('default', 'recentcomments');
  }
  
  public function getcontent($id, $sidebar) {
    $manager = tcommentmanager::instance();
    $recent = $manager->getrecent($this->maxcount);
    if (count($recent) == 0) return '';
    $result = '';
    $theme = ttheme::instance();
    $tml = $theme->getwidgetitem('comments', $sidebar);
    $url = litepublisher::$site->url;
    $args = targs::instance();
    $args->onrecent = tlocal::get('comment', 'onrecent');
    foreach ($recent as $item) {
      $args->add($item);
      $args->link = $url . $item['posturl'];
      $args->content = tcontentfilter::getexcerpt($item['content'], 120);
      $result .= $theme->parsearg($tml,$args);
    }
    return $theme->getwidgetcontent($result, 'comments', $sidebar);
  }
  
  public function changed() {
    $this->expire();
  }
  
}//class
