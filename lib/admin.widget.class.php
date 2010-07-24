<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminwidget extends tdata {
public $widget;
protected $html;
protected $lang;

protected function create() {
//parent::instance();
$this->html = THtmlResource ::instance();
    $this->html->section = 'widgets';
    $this->lang = tlocal::instance('widgets');
}

protected function getadminurl() {
return litepublisher::$options->url . '/admin/widgets/' . litepublisher::$options->q . 'idwidget=';
}

  protected function dogetcontent(twidget $widget, targs $args){
$this->error('Not implemented');
}

public function getcontent(){
$form = $this->dogetcontent($this->widget, targs::instance());
return $this->optionsform($form);
}

  public function processform()  {
$widget = $this->widget;
$widget->lock();
if (isset($_POST['title'])) $widget->title = $_POST['title'];
$this->doprocessform($widget);
$widget->unlock();
return $this->html->h2->updated;
}

  protected function doprocessform(twidget $widget)  {
$this->error('Not implemented');
}

protected function optionsform($content) {
    $args = targs::instance();
$args->title = $this->widget->title;
$args->content = $content;
return $this->html->optionsform($args);
}

}//class

class tadmintagswidget extends tadminwidget {

  public static function instance($id = null) {
    return getinstance(__class__);
  }

  protected function dogetcontent(twidget $widget, targs $args){
$args->showcount = $widget->showcount;
$args->maxcount = $widget->maxcount;
$args->combo = tadminwidgets::getcombo('sort', tlocal::$data['sortnametags'], $widget->sortname);
return $this->html->tagsform($args);
}

  protected function doprocessform(twidget $widget)  {
extract($_POST, EXTR_SKIP);
$widget->maxcount = (int) $maxcount;
$widget->showcount = isset($showcount);
$widget->sortname = $sort;
}

}//class

class tadminmaxcount extends tadminwidget {

  public static function instance($id = null) {
    return getinstance(__class__);
  }

  protected function dogetcontent(twidget $widget, targs $args){
$args->maxcount = $widget->maxcount;
return $this->html->maxcountform($args);
}

  protected function doprocessform(twidget $widget)  {
$widget->maxcount = (int) $_POST['maxcount'];
}

}//class

class tadminshowcount extends tadminwidget {

  public static function instance($id = null) {
    return getinstance(__class__);
  }

  protected function dogetcontent(twidget $widget, targs $args){
$args->showcount = $widget->showcount;
return $this->html->showcountform($args);
}

  protected function doprocessform(twidget $widget)  {
$widget->showcount = isset($_POST['showcount']);
}

}//class

class tadminfriendswidget extends tadminwidget {

  public static function instance($id = null) {
    return getinstance(__class__);
  }

  protected function dogetcontent(twidget $widget, targs $args){
$args->maxcount = $widget->maxcount;
$args->redir = $widget->redir;
return $this->html->friendsform($args);
}

  protected function doprocessform(twidget $widget)  {
$widget->maxcount = (int) $_POST['maxcount'];
$widget->redir = isset($_POST['redir']);
}

}//class

class tadminorderwidget extends tadminwidget {

  public static function instance($id = null) {
    return getinstance(__class__);
  }

  protected function dogetcontent(twidget $widget, targs $args){
$widgets =twidgets::instance();
$id = $widgets->find($widget);
$item = &$widgets->finditem($id);
if ($item) {
$args->sitebarcombo = tadminwidgets::getcombo('sitebar', tadminwidgets::getsitebarnames(3), $item['sitebar']);
$args->ordercombo = tadminwidgets::getcombo('order', range(-1, 10), $item['order'] + 1);
$args->ajax = $item['ajax'];
return $this->html->orderform($args);
}
}

  protected function doprocessform(twidget $widget)  {
$widgets = twidgets::instance();
$item = &$widgets->finditem($widgets->find($widget));
if ($item) {
$item['sitebar'] = (int) $_POST['sitebar'];
$item['order'] = ((int) $_POST['order'] - 1);
$item['ajax'] = isset($_POST['ajax']);
$widgets->save();
}
}

}//class

class tadmincustomwidget extends tadminwidget {

  public static function instance($id = null) {
    return getinstance(__class__);
  }

public static function gettemplates() {
$result = array();
$lang = tlocal::instance('widgets');
$result['widget'] = $lang->defaulttemplate;
foreach (tthemeparser::getwidgetnames() as $name) {
$result[$name] = $lang->$name;
}
return $result;
}

public function getcontent() {
$widget = $this->widget;
    $args = targs::instance();
$id = isset($_GET['idwidget']) ? (int) $_GET['idwidget'] : 0;
if (isset($widget->items[$id])) {
$item = $widget->items[$id];
$args->mode = 'edit';
} else {
$args->mode = 'add';
$item = array(
'title' => '',
'content' => '',
'template' => 'widget'
);
}

$html= $this->html;
$args->title = $item['title'];
$args->text = $item['content'];
$args->combo =tadminwidgets::getcombo('template', self::gettemplates(), $item['template']);
$args->content = $html->customform($args);
$result = $html->optionsform($args);

      $list = '';
      $args->adminurl = $this->adminurl;'idwidget';
      foreach ($widget->items as $id => $item) {
        $args->idwidget = $id;
        $args->add($item);
        $list .= $html->customitem($args);
      }
      $result .= sprintf($html->customitems, $list);
return $result;
}

  public function processform()  {
extract($_POST, EXTR_SKIP);
$idwidget = (int) $_GET['idwidget'];
$widget = $this->widget;
switch ($mode) {
case 'add':
$_GET['idwidget'] = $widget->add($title, $text, $template);
break;

case 'edit':
$widget->edit($idwidget, $title, $text, $template);
break;
}
}

}//class
class tadminlinkswidget extends tadminwidget {

  public static function instance($id = null) {
    return getinstance(__class__);
  }

public function getcontent() {
$widget = $this->widget;
$html= $this->html;
    $args = targs::instance();
$args->title = $widget->title;
$args->redir = $widget->redir;
$args->content = $html->linksoptions ($args);
$result = $html->optionsform($args);

$id = isset($_GET['idlink']) ? (int) $_GET['idlink'] : 0;
if (isset($widget->items[$id])) {
$item = $widget->items[$id];
$args->mode = 'edit';
} else {
$args->mode = 'add';
$item = array(
    'url' => '',
    'title' => '',
    'anchor' => ''
);
}

$args->add($item);
$result .= $html->linkform($args);

      $args->adminurl = $this->adminurl . $_GET['idwidget'] . '&idlink';
$result .= $html->linkstableheader ();
      foreach ($widget->items as $id => $item) {
        $args->id = $id;
        $args->add($item);
        $result .= $html->linkitem($args);
      }
      $result .= $html->linkstablefooter();
return $result;
}

  public function processform()  {
$widget = $this->widget;
$widget->lock();
      if (isset($_POST['delete'])) {
        foreach ($_POST as $key => $value) {
$id = (int) $value;
          if (isset($widget->items[$id]))  $widget->delete($id);
          }
} elseif (isset($_POST['mode'])) {
extract($_POST, EXTR_SKIP);
switch ($mode) {
case 'add':
$_GET['idlink'] = $widget->add($url, $linktitle, $anchor);
break;

case 'edit':
$widget->edit((int) $_GET['idlink'], $url, $linktitle, $anchor);
break;
}
} else {
extract($_POST, EXTR_SKIP);
$widget->title = $title;
$widget->redir = isset($redir);
}
$widget->unlock();
return $this->html->h2->updated;
}

}//class

class tadminmetawidget extends tadminwidget {

  public static function instance($id = null) {
    return getinstance(__class__);
  }

  protected function dogetcontent(twidget $widget, targs $args){
$args->add($widget->meta);
return $this->html->metaform($args);
}

  protected function doprocessform(twidget $widget)  {
foreach ($widget->meta as $name => $value) {
$widget->data['meta'][$name] = isset($_POST[$name]);
}
}

}//class

class tadminhomewidgets extends tadminwidget {

  public static function instance($id = null) {
    return getinstance(__class__);
  }

public function getcontent(){
$home = thomepage::instance();
$args = targs::instance();
$args->ajax = $home->ajax;
$args->defaultsitebar = $home->defaultsitebar;
$result = $this->html->homeform($args);
if (!$home->defaultsitebar) {
$result .= tadminwidgets::getsitebarsform($home->sitebars);
}
return $result;
}

  public function processform()  {
$home = thomepage::instance();
$home->lock();
if (isset($_POST['homeoptions'])) {
$home->ajax = isset($_POST['ajax']);
$home->defaultsitebar = isset($_POST['defaultsitebar']);
} else {
var_dump($_POST);
tadminwidgets::setsitebars($home->sitebars);
}
$home->unlock();
return $this->html->h2->updated;
}

}//class

?>