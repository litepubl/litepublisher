<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcomments extends titems {
  public $rawtable;
  private $pid;
  
  public static function i($pid = 0) {
    $result = getinstance(__class__);
    if ($pid > 0) $result->pid = $pid;
    return $result;
  }
  
  protected function create() {
    $this->dbversion = true;
    parent::create();
    $this->table = 'comments';
    $this->rawtable = 'rawcomments';
    $this->basename = 'comments';
    $this->addevents('edited');
    $this->pid = 0;
  }
  
  public function add($idauthor, $content, $status, $ip) {
    if ($idauthor == 0) $this->error('Author id = 0');
    $filter = tcontentfilter::i();
    $filtered = $filter->filtercomment($content);
    
    $item = array(
    'post' => $this->pid,
    'parent' => 0,
    'author' => (int) $idauthor,
    'posted' => sqldate(),
    'content' =>$filtered,
    'status' => $status
    );
    
    $id = (int) $this->db->add($item);
    $item['rawcontent'] = $content;
    
    $comusers = tcomusers::i();
    if ($author = $comusers->getitem($idauthor)) {
      $item = $item + $author;
    } else {
      $this->error(sprintf('Author %d not found', $idauthor));
    }
    
    $item['id'] = $id;
    $this->items[$id] = $item;
    
    $this->getdb($this->rawtable)->add(array(
    'id' => $id,
    'created' => sqldate(),
    'modified' => sqldate(),
    'ip' => $ip,
    'rawcontent' => $content,
    'hash' => basemd5($content)
    ));
    
    return $id;
  }
  
  public function edit($id, $idauthor, $content) {
    if (!$this->itemexists($id)) return false;
    if ($idauthor == 0) $idauthor = $this->db->getvalue($id, 'author');
    $filter = tcontentfilter::i();
    $item = array(
    'id' => (int) $id,
    'author' => (int)$idauthor,
    'content' => $filter->filtercomment($content)
    );
    
    $this->db->updateassoc($item);
    
    $item['rawcontent'] = $content;
    $this->items[$id] = $item + $this->items[$id];
    
    $this->getdb($this->rawtable)->updateassoc(array(
    'id' => $id,
    'modified' => sqldate(),
    'rawcontent' => $content,
    'hash' => basemd5($content)
    ));
    
    return true;
  }
  
  public function getcomment($id) {
    return new tcomment($id);
  }
  
  public function delete($id) {
    return $this->db->setvalue($id, 'status', 'deleted');
  }
  
  public function setstatus($id, $status) {
    return $this->db->setvalue($id, 'status', $status);
  }
  
  public function getcount($where = '') {
    return $this->db->getcount($where);
  }
  
  public function select($where, $limit) {
    if ($where != '') $where .= ' and ';
    $comusers = tcomusers::i();
    $authors = $comusers->thistable;
    $table = $this->thistable;
    $res = litepublisher::$db->query("select $table.*, $authors.name, $authors.email, $authors.url, $authors.trust from $table, $authors
    where $where $authors.id = $table.author $limit");
    
    return $this->res2items($res);
  }
  
  public function getraw() {
    return $this->getdb($this->rawtable);
  }
  
  public function getapprovedcount() {
    return $this->db->getcount("post = $this->pid and status = 'approved'");
  }
  
  public function insert($idauthor, $content, $ip, $posted, $status) {
    $filter = tcontentfilter::i();
    $filtered = $filter->filtercomment($content);
    $item = array(
    'post' => $this->pid,
    'parent' => 0,
    'author' => $idauthor,
    'posted' => sqldate($posted),
    'content' =>$filtered,
    'status' => $status
    );
    
    $id =$this->db->add($item);
    $item['rawcontent'] = $content;
    $this->items[$id] = $item;
    
    $this->getdb($this->rawtable)->add(array(
    'id' => $id,
    'created' => sqldate($posted),
    'modified' => sqldate(),
    'ip' => $ip,
    'rawcontent' => $content,
    'hash' => basemd5($content)
    ));
    
    return $id;
  }
  
  
  public function getmoderator() {
    if (!litepublisher::$options->admincookie) return false;
    if (litepublisher::$options->group == 'admin') return true;
    $groups = tusergroups::i();
    return $groups->hasright(litepublisher::$options->group, 'moderator');
  }
  
  public function getcontent() {
    $result = $this->getcontentwhere('approved', '');
    if (!$this->moderator) return $result;
    $theme = ttheme::i();
    tlocal::usefile('admin');
    $args = targs::i();
    $post = tpost::i($this->pid);
    if ($post->commentpages == litepublisher::$urlmap->page) {
      $result .= $this->getcontentwhere('hold', '');
    } else {
      //add empty list of hold comments
      $args->comment = '';
      $result .= $theme->parsearg($theme->templates['content.post.templatecomments.holdcomments'], $args);
    }
    
    $args->comments = $result;
    return $theme->parsearg($theme->templates['content.post.templatecomments.moderateform'], $args);
  }
  
  public function getholdcontent($idauthor) {
    if (litepublisher::$options->admincookie) return '';
    return $this->getcontentwhere('hold', "and $this->thistable.author = $idauthor");
  }
  
  private function getcontentwhere($status, $whereauthor) {
    $result = '';
    $post = tpost::i($this->pid);
    if ($status == 'approved') {
      if (litepublisher::$options->commentpages ) {
        $page = litepublisher::$urlmap->page;
        if (litepublisher::$options->comments_invert_order) $page = max(0, $post->commentpages  - $page) + 1;
        $count = litepublisher::$options->commentsperpage;
        $from = ($page - 1) * $count;
      } else {
        $from = 0;
        $count = $post->commentscount;
      }
    } else {
      $from = 0;
      $count = litepublisher::$options->commentsperpage;
    }
    
    $table = $this->thistable;
    $items = $this->select("$table.post = $this->pid $whereauthor  and $table.status = '$status'",
    "order by $table.posted asc limit $from, $count");
    
    $args = targs::i();
    $args->from = $from;
    $comment = new tcomment(0);
    ttheme::$vars['comment'] = $comment;
    $lang = tlocal::i('comment');
    $theme = ttheme::i();
    if ($ismoder = $this->moderator) {
      tlocal::usefile('admin');
      $moderate =$theme->templates['content.post.templatecomments.comments.comment.moderate'];
    } else {
      $moderate = '';
    }
    $tmlcomment= $theme->gettag('content.post.templatecomments.comments.comment');;
    $tml = strtr((string) $tmlcomment, array(
    '$moderate' => $moderate,
    '$quotebuttons' => $post->commentsenabled ? $tmlcomment->quotebuttons : ''
    ));
    
    $index = $from;
    $class1 = $tmlcomment->class1;
    $class2 = $tmlcomment->class2;
    foreach ($items as $id) {
      $comment->id = $id;
      $args->index = ++$index;
      $args->class = ($index % 2) == 0 ? $class1 : $class2;
      $result .= $theme->parsearg($tml, $args);
    }
    unset(ttheme::$vars['comment']);
    if (!$ismoder) {
      if ($result == '') return '';
    }
    
    if ($status == 'hold') {
      $tml = $theme->templates['content.post.templatecomments.holdcomments'];
    } else {
      $tml = $theme->templates['content.post.templatecomments.comments'];
    }
    
    $args->from = $from + 1;
    $args->comment = $result;
    return $theme->parsearg($tml, $args);
  }
  
}//class

class tcomment extends tdata {
  
  public function __construct($id) {
    if (!isset($id)) return false;
    parent::__construct();
    $this->table = 'comments';
    $id = (int) $id;
    if ($id > 0) $this->setid($id);
  }
  
  public function setid($id) {
    $comments = tcomments::i();
    $this->data = $comments->getitem($id);
  }
  
  public function save() {
    extract($this->data, EXTR_SKIP);
    $this->db->UpdateAssoc(compact('id', 'post', 'author', 'parent', 'posted', 'status', 'content'));
    
    $this->getdb($this->rawtable)->UpdateAssoc(array(
    'id' => $id,
    'modified' => sqldate(),
    'rawcontent' => $rawcontent,
    'hash' => basemd5($rawcontent)
    ));
  }
  
  public function getauthorlink() {
    $name = $this->data['name'];
    $url = $this->data['url'];
    if ($url == '')  return $name;
    $manager = tcommentmanager::i();
    if ($manager->hidelink || !$manager->checktrust($this->trust)) return $name;
    $rel = $manager->nofollow ? 'rel="nofollow"' : '';
    if ($manager->redir) {
      return sprintf('<a %s href="%s/comusers.htm%sid=%d">%s</a>',$rel,
      litepublisher::$site->url, litepublisher::$site->q, $this->author, $name);
    } else {
      return sprintf('<a class="url fn" %s href="%s">%s</a>',
      $rel,$url, $name);
    }
  }
  
  public function getdate() {
    $theme = ttheme::i();
    return tlocal::date($this->posted, $theme->templates['content.post.templatecomments.comments.comment.date']);
  }
  
  public function Getlocalstatus() {
    return tlocal::get('commentstatus', $this->status);
  }
  
  public function getposted() {
    return strtotime($this->data['posted']);
  }
  
  public function setposted($date) {
    $this->data['posted'] = sqldate($date);
  }
  
  public function  gettime() {
    return date('H:i', $this->posted);
  }
  
  public function getwebsite() {
    return $this->data['url'];
  }
  
  public function geturl() {
    $post = tpost::i($this->post);
    return $post->link . "#comment-$this->id";
  }
  
  public function getposttitle() {
    $post = tpost::i($this->post);
    return $post->title;
  }
  
  public function getrawcontent() {
    if (isset($this->data['rawcontent'])) return $this->data['rawcontent'];
    $comments = tcomments::i($this->post);
    return $comments->raw->getvalue($this->id, 'rawcontent');
  }
  
  public function setrawcontent($s) {
    $this->data['rawcontent'] = $s;
    $filter = tcontentfilter::i();
    $this->data['content'] = $filter->filtercomment($s);
  }
  
  public function getip() {
    if (isset($this->data['ip'])) return $this->data['ip'];
    $comments = tcomments::i($this->post);
    return $comments->raw->getvalue($this->id, 'ip');
  }
  
}//class

?>