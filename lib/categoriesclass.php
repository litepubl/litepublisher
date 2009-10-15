<?php

class TCategories extends TCommonTags {
  private $contents;
  //public  $defaultid;
  
  public static function instance() {
    return getnamedinstance('categories', __class__);
  }
  
  protected function create() {
    parent::create();
$this->table = 'categories';
    $this->basename = 'categories' ;
    $this->contents = array();
    $this->data['defaultid']=  1;
  }
  
  public function setdefaultid($id) {
    if (($id != $this->defaultid) && isset($this->items[$id])) {
      $this->data['defaultid'] = $id;
      $this->save();
    }
  }
  
  public function Delete($id) {
    parent::Delete($id);
    @unlink($this->GetContentFilename($id));
  }
  
  private function GetContentFilename($id) {
    global $paths;
    return $paths['data'] . 'categories' . DIRECTORY_SEPARATOR . $id . '.php';
  }
  
  public function GetItemContent($id) {
    if (!isset($this->contents[$id])) {
      if (!TFiler::UnserializeFromFile($this->GetContentFilename($id), $this->contents[$id])) $this->contents[$id] = false;
    }
    return $this->contents[$id];
  }
  
  public function SetItemContent($id, $content) {
    $this->contents[$id] = array(
    'content' => $content,
    'excerpt' => TContentFilter::GetExcerpt($content, 80)
    );
    
    TFiler::SerializeToFile($this->GetContentFilename($id), $this->contents[$id]);
  }
  
  public function Getdescription() {
    if ($item = $this->GetItemContent($this->id)) {
      return $item['excerpt'];
    }
    return '';
  }
  
  public function GetTemplateContent() {
    $result = '';
    if ($item = $this->GetItemContent($this->id)) {
      $result .= $item['content'];
    }
    
    $result .= parent::GetTemplateContent();
    return $result;
  }
  
}//class
?>