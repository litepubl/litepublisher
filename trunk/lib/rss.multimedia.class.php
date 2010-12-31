<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class trssMultimedia extends tevents {
  public $domrss;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'rssmultimedia';
    $this->data['feedburner'] = '';
  }
  
  public function fileschanged() {
    litepublisher::$urlmap->expiredclass(get_class($this));
  }
  
  public function request($arg) {
    $result = '';
    if (($arg == null) && ($this->feedburner  != '')) {
      $result .= "<?php
      if (!preg_match('/feedburner|feedvalidator/i', \$_SERVER['HTTP_USER_AGENT'])) {
        if (function_exists('status_header')) status_header( 307 );
        header('Location:$this->feedburner');
        header('HTTP/1.1 307 Temporary Redirect');
        return;
      }
      ?>";
    }
    
    $result .= turlmap::xmlheader();
    
    $this->domrss = new tdomrss;
    $this->domrss->CreateRootMultimedia(litepublisher::$site->url. litepublisher::$urlmap->url, 'media');
    
    $list = $this->getrecent($arg, litepublisher::$options->perpage);
    foreach ($list as $id) {
      $this->addfile($id);
    }
    
    $result .= $this->domrss->GetStripedXML();
    return $result;
  }
  
  private function getrecent($type, $count) {
    $files = tfiles::instance();
    if (dbversion) {
      $sql = $type == '' ? '' : "media = '$type' ";
      return $files->select($sql . 'parent = 0', " order by posted desc limit $count");
    } else {
      $result = array();
      $list = array_reverse(array_keys($files->items));
      foreach ($list as $id) {
        $item = $files->items[$id];
        if ($item['parent'] != 0) continue;
        if ($type != '' && $type != $item['media']) continue;
        $result[] = $id;
        if (--$count <= 0) break;
      }
      return $result;
    }
  }
  
  public function addfile($id) {
    $files = tfiles::instance();
    $file = $files->getitem($id);
    $posts = $files->itemsposts->getposts($id);
    
    if (count($posts) == 0) {
      $postlink = litepublisher::$site->url . '/';
    } else {
      $post = tpost::instance($posts[0]);
      $postlink = $post->link;
    }
    
    $item = $this->domrss->AddItem();
    tnode::addvalue($item, 'title', $file['title']);
    tnode::addvalue($item, 'link', $postlink);
    tnode::addvalue($item, 'pubDate', $file['posted']);
    
    $media = tnode::add($item, 'media:content');
    tnode::attr($media, 'url', $files->geturl($id));
    tnode::attr($media, 'fileSize', $file['size']);
    tnode::attr($media, 'type', $file['mime']);
    tnode::attr($media, 'medium', $file['media']);
    tnode::attr($media, 'expression', 'full');
    
    if ($file['width'] > 0 && $file['height'] > 0) {
      tnode::attr($media, 'height', $file['height']);
      tnode::attr($media, 'width', $file['width']);
    }
    
    if (!empty($file['bitrate'])) tnode::attr($media, 'bitrate', $file['bitrate']);
    if (!empty($file['framerate'])) tnode::attr($media, 'framerate', $file['framerate']);
    if (!empty($file['samplingrate'])) tnode::attr($media, 'samplingrate', $file['samplingrate']);
    if (!empty($file['channels'])) tnode::attr($media, 'channels', $file['channels']);
    if (!empty($file['duration'])) tnode::attr($media, 'duration', $file['duration']);
    
    $md5 = tnode::addvalue($item, 'media:hash', $file['md5']);
    tnode::attr($md5, 'algo', "md5");
    
    if (!empty($file['keywords'])) {
      tnode::addvalue($item, 'media:keywords', $file['keywords']);
    }
    
    if (!empty($file['description'])) {
      $description = tnode::addvalue($item, 'description', $file['description']);
      tnode::attr($description, 'type', 'html');
    }
    
    if ($file['preview'] > 0) {
      $idpreview = $file['preview'];
      $preview = $files->getitem($idpreview);
      $thumbnail  = tnode::add($item, 'media:thumbnail');
      tnode::attr($thumbnail, 'url', $files->geturl($idpreview));
      if ($preview['width'] > 0 && $preview['height'] > 0) {
        tnode::attr($thumbnail, 'height', $preview['height']);
        tnode::attr($thumbnail, 'width', $preview['width']);
      }
    }
    
  }
  
  public function setfeedburner($url) {
    if (($this->feedburner != $url)) {
      $this->data['feedburner'] = $url;
      $this->save();
      litepublisher::$urlmap->clearcache();
    }
  }
  
}//class

?>