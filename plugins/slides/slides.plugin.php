<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tslidesplugin extends tplugin {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  public function install() {
    $home = thomepage::instance();
    $home->content = $this->gethtml()  . $home->rawcontent;
    $home->save();
    litepublisher::$urlmap->clearcache();
  }
  
  public function uninstall() {
    $home = thomepage::instance();
    $html = $this->gethtml();
    $home->content = str_replace($html, '', $home->rawcontent);
    $home->save();
    litepublisher::$urlmap->clearcache();
  }
  
  public function gethtml() {
    $template = ttemplate::instance();
    $result = $template->getjavascript('/plugins/slides/slides.plugin.min.js');
$result .= '<div id="slides-holder"></div>';
return $result;
  }
  
}//class