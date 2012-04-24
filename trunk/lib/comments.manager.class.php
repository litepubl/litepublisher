<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcommentmanager extends tevents_storage {

  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'commentmanager';
    $this->addevents('added', 'deleted', 'edited', 'changed', 'approved',
    'authoradded', 'authordeleted', 'authoredited',
'is_spamer', 'onstatus');
}

  public function getcount() {
    litepublisher::$db->table = 'comments';
    return litepublisher::$db->getcount();
  }
  
  public function addcomuser($name, $email, $url, $ip) {
    $email = strtolower(trim($email));
    if ($id = $this->find($name, $email, $url)) {
      $this->db->setvalue($id, 'ip', $ip);
      return $id;
    }
    
    if (($parsed = @parse_url($url)) &&  is_array($parsed) ) {
      if ( empty($parsed['host'])) {
        $url = '';
      } else {
        if ( !isset($parsed['scheme']) || !in_array($parsed['scheme'], array('http','https')) ) $parsed['scheme']= 'http';
        if (!isset($parsed['path'])) $parsed['path'] = '';
        $url = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
        if (!empty($parsed['query'])) $url .= '?' . $parsed['query'];
      }
    } else {
      $url = '';
    }
    $id = $this->db->add(array(
    'trust' => 0,
    'name' => $name,
    'url' => $url,
    'email' => $email,
    'ip' => $ip,
    'cookie' => md5uniq(),
    ));
    
$this->authoradded($id);
    return $id;
  }
  
  public function add($idpost, $idauthor, $content, $ip) {
    $status = $this->createstatus($idpost, $idauthor, $content, $ip);
    if (!$status) return false;
    $comments = tcomments::i();
    $id = $comments->add($idpost, $idauthor,  $content, $status, $ip);
    $this->dochanged($id, $idpost);
    $this->added($id, $idpost);
    $this->sendmail($id);
    return $id;
  }

  public function edit($id, $content) {
    $comments = tcomments::i();
    if (!$comments->edit($id, $idauthor,  $content)) return false;
        $this->dochanged($id, $idpost);
    $this->edited($id, $idpost);
    return true;
  }
  
  public function reply($idparent, $content) {
$idauthor = 1; //admin
    $status = 'approved';
    $comments = tcomments::i();
$idpost = $comments->getvalue($idparent, 'post');
    $id = $comments->add($idpost, $idauthor,  $content, $status, '');
$comments->setvalue($id, 'parent', $idreply);
    
    $this->dochanged($id, $idpost);
    $this->added($id, $idpost);
    //$this->sendmail($id, $idpost);
    return $id;
  }
  
  private function dochanged($id, $idpost) {
      $comments = tcomments::i();
      $count = $comments->db->getcount("post = $idpost and status = 'approved'");
      $comments->getdb('posts')->setvalue($idpost, 'commentscount', $count);
      //update trust
      try {
        $item = $comments->getitem($id);
        $idauthor = $item['author'];
        $comusers = tcomusers::i($idpost);
        $comusers->setvalue($idauthor, 'trust', $comments->db->getcount("author = $idauthor and status = 'approved' limit 5"));
      } catch (Exception $e) {
      }
    }
    
    $this->changed($id, $idpost);
  }
  
  public function delete($id) {
    $comments = tcomments::i();
    if ($comments->delete($id)) {
      $this->deleted($id, $idpost);
      $this->dochanged($id, $idpost);
      return true;
    }
    return false;
  }
  
  public function setstatus($id, $$status) {
    if (!in_array($status, array('approved', 'hold', 'spam')))  return false;
    $comments = tcomments::i($idpost);
    if ($comments->setstatus($id, $status)) {
      $this->dochanged($id, $idpost);
      return true;
    }
    return false;
  }
  
  public function checktrust($value) {
    return $value >= $this->trustlevel;
  }
  
  public function trusted($idauthor) {
    if (!dbversion) return true;
    $comusers = tcomusers::i(0);
    $item = $comusers->getitem($idauthor);
    return $this->checktrust($item['trust']);
  }
  
  public function sendmail($id) {
    if ($this->sendnotification) {
litepublisher::$urlmap->onclose($this, 'send_mail', $id);
}
}

  public function send_mail($id) {
    $comments = tcomments::i($idpost);
    $comment = $comments->getcomment($id);
    ttheme::$vars['comment'] = $comment;
    $args = targs::i();
    $adminurl = litepublisher::$site->url . '/admin/comments/'. litepublisher::$site->q . "id=$id&post=$idpost";
    $ref = md5(litepublisher::$secret . $adminurl);
    $adminurl .= "&ref=$ref&action";
    $args->adminurl = $adminurl;
    
    $mailtemplate = tmailtemplate::i('comments');
    $subject = $mailtemplate->subject($args);
    $body = $mailtemplate->body($args);
    return tmailer::sendtoadmin($subject, $body, false);
  }
  
  public function createstatus($idpost, $idauthor, $content, $ip) {
    $status = $this->onstatus($idpost, $idauthor, $content, $ip);
    if (false ===  $status) return false;
    if ($status == 'spam') return false;
    if (($status == 'hold') || ($status == 'approved')) return $status;
    if (!litepublisher::$options->filtercommentstatus) return litepublisher::$options->DefaultCommentStatus;
    if (litepublisher::$options->DefaultCommentStatus == 'approved') return 'approved';

    if ($this->trusted($idauthor)) return  'approved';
    return 'hold';
  }
  
  public function canadd($idauthor) {
return !$this->is_spamer($idauthor);
  }
  
  public function checkduplicate($idpost, $content) {
    $comments = tcomments::i($idpost);
    $content = trim($content);
      $hash = basemd5($content);
      return $comments->raw->findid("hash = '$hash'");
  }
  
}//class