<?php
/**
 * Lite Publisher 
 * Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
 * Dual licensed under the MIT (mit.txt) 
 * and GPL (gpl.txt) licenses.
**/

interface itemplate {
  public function request($arg);
  public function gettitle();
  public function gethead();
  public function getkeywords();
  public function getdescription();
  public function GetTemplateContent();
}

interface iadvancedtemplate {
public function getsitebar($index, &$content);
public function afterrequest(&$content);
}

interface  icomments {
//comment form
  public function gethold($author);
  public function IndexOfRawContent($s);
//holditems property used by TAdminModerator
  public function getholditems();
//pingback xmlrpc
 public function haspingback($url);
}

interface imultimedia {
}

?>