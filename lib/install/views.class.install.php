<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function tviewsInstall($self) {
  $self->lock();
    tlocal::loadlang('admin');
$lang = tlocal::instance('names');
  $default = $self->add($lang->default);
$def = tview::instance($default);
$def->sidebars = array(array(), array(), array());

$admin = $self->add($lang->adminpanel);

$self->defaults = array(
'post' => $default,
'menu' => $default,
'category' => $default,
'tag' => $default,
'admin' => $admin
);

  $self->unlock();
}

?>