<?php

class TAdminPosts extends TAdminPage {
 
 public static function &Instance() {
  return GetInstance(__class__);
 }
 
 protected function CreateData() {
  parent::CreateData();
  $this->basename = 'posts';
 }
 
 public function Getcontent() {
  global $Options;
  if (!isset($_GET) || !isset($_GET['action'])) {
   return $this->GetPostsList();
  }
  
  $id = (int) $_GET['postid'];
  $html = &THtmlResource::Instance();
  $html->section = $this->basename;
  
  $posts= &TPosts::Instance();
  if (!$posts->ItemExists($id)) {
   return $html->notfound;
  }
  $post = &TPost::Instance($id);
  
  $result ='';
  if  (isset($_GET['confirm']) && ($_GET['confirm'] == 1)) {
   switch ($_GET['action']) {
    case 'delete' :
    $posts->Delete($id);
    break;
    
    case 'setdraft':
    $post->status = 'draft';
    $posts->Edit($post);
    break;
    
    case 'publish':
    $post->status = 'published';
    $posts->Edit($post);
    break;
   }
   
   $result .=  sprintf($html->confirmed, TLocal::$data['poststatus'][$_GET['action']], "<a href='$Options->url$post->url'>$post->title</a>");
  } else {
   $lang = &TLocal::$data[$this->basename];
   $confirm = sprintf($lang['confirm'], $lang[$_GET['action']], "<a href='$Options->url$post->url'>$post->title</a>");
   $yes = TLocal::$data['default']['yesword'];
   eval('$result .= "'. $html->confirmform . '\n";');
  }
  return $result;
 }
 
 public function GetPostsList() {
  global $Options, $Urlmap;
  $result = '';
  $html = &THtmlResource::Instance();
  $html->section = $this->basename;
  
  $posts = &TPosts::Instance();
  $from = max(0, count($posts->items) - $Urlmap->pagenumber * 100);
  $items = array_slice($posts->items, $from, 100, true);
  $result .= sprintf($html->count, $from, $from + count($items), count($posts->items));
  $result .= $html->listhead;
  $list = '';
  foreach ($items  as $id => $item) {
   $post = &TPost::Instance($id);
   $status = TLocal::$data['poststatus'][$post->status];
   eval('$list="\n' . $html->itemlist . '" . $list;');
  }
  $result .= $list;
  $result .= $html->listfooter;
  $result = str_replace("'", '"', $result);
  
  $TemplatePost = &TTemplatePost::Instance();
  $result .= $TemplatePost ->PrintNaviPages('/admin/posts/', $Urlmap->pagenumber, ceil(count($posts->items)/100));
  return $result;
 }
 
}//class
?>