<?php

function update527() {

$cm = tcommentmanager::i();
$data = new tdata();
$data->basename = 'commentmanager';
$data->load();
$cm->data = $data->data;
    $cm->data['canedit'] =  true;
    $cm->data['candelete'] =  true;
$cm->data['confirmemail'] = false;
$cm->data['confirmlogged'] = false;
$cm->data['confirmguest'] = true;
$cm->data['confirmcomuser'] = true;

    $cm->data['idguest'] =  tusers::i()->add(array(
'email' => '',
'name' => tlocal::get('default', 'guest'),
'status' => 'approved',
'idgroups' => 'commentator'
));

$spam = new tdata();
$spam->basename = 'spamfilter';
$spam->load();
if (isset($spam->data['events'])) {
foreach ($spam->data['events'] as $eventname => $events) {
$cm->data['events'][$eventname] = $events;
}
}

$cm->save();

  tposts::unsub($cm);
  tposts::i()->addevent('deleted', 'tcomments', 'postdeleted');

litepublisher::$classes->add('tjsonserver', 'jsonserver.class.php');
litepublisher::$classes->add('tjsoncomments', 'json.comments.class.php');
litepublisher::$classes->add('Tadmincommentmanager', 'admin.commentmanager.class.php');
litepublisher::$classes->add('tsession', 'session.class.php');
litepublisher::$classes->items['tusers'][0] = 'kernel.php';
litepublisher::$classes->items['tkeptcomments'][2] = 'comments.trap.class.php';

unset(litepublisher::$classes->items['tspamfilter']);
unset(litepublisher::$classes->classes['spamfilter']);
unset(litepublisher::$classes->items['tkeptcomments']);
unset(litepublisher::$classes->items['tcomusers']);
unset(litepublisher::$classes->classes['comusers']);

  litepublisher::$options->comments_status = 'guest';

$admin = tadminmenus::i();
$admin->items[$admin->url2id('/admin/options/comments/')]['class'] = 'tadmincommentmanager';
$admin->save();

tjsmerger::i()->deletefile('moderate', '/js/litepublisher/rpc.min.js');

ttemplatecomments::i()->install();

$db = litepublisher::$db;
$db->table = 'users';
$db->insertrow($db->assoctorow(array(
'id' =>1,
    'email' =>litepublisher::$options->email,
    'name' => litepublisher::$site->author,
    'website' => litepublisher::$site->url . '/',
    'password' => litepublisher::$options->password,
    'cookie' => litepublisher::$options->cookie,
    'expired' => sqldate(litepublisher::$options->cookieexpired ),
    'status' => 'approved',
    'idgroups' => '1',
)));

$db->table = 'usergroup';
        $db->add(array(
        'iduser' => '1',
        'idgroup' => 1
        ));

$man = tdbmanager::i();
//$man->alter($table, "add `includeparents` boolean default " . ($tags->includeparents ? 'true' : 'false'));
$man->delete_enum('users', 'status', 'lock');
$man->addenum('users', 'status', 'comuser');

$man->alter('posts', "add `comments_status` enum('closed','reg','guest','comuser') default 'comuser'");

$db->table = 'posts';
$db->update("comments_status = 'closed'", "commentsenabled = 0");
$man->alter('posts', "drop commentsenabled");

$groups = tusergroups::i();
if ($idgroup = $groups->getidgroup('subscriber')) {
$groups->items[$idgroup]['name'] = 'commentator';
$groups->save();
} else {
$idgroup = $groups->getidgroup('commentator');
}

$man->alter('comments', "add tmp int unsigned NOT NULL default '0'");

// $map for subscribers
$map = array();
    $from = 0;
$db->table = 'comusers';
    while ($items = $db->res2assoc($db->query("select * from $db->comusers limit $from, 100"))) {
$from += count($items);
      foreach ($items as $item) {
$db->table = 'users';
if ($id = $db->findid('email = '. dbquote($item['email']))) {
$u = $db->getitem($id);
//echo $item['id'], ' ', $item['email'], ' ', $item['name'], '<br>';
//echo "found $id ", $u['email'], ' ', $u['name'], '<br>';
} else {
$id = $db->add(array(
    'email' => $item['email'],
    'name' =>$item['name'],
    'website' => $item['url'],
    'password' => '',
    'cookie' =>  $item['cookie'],
    'expired' => sqldate(),
    'idgroups' => "$idgroup",
    'trust' => $item['trust'],
    'status' => 'comuser',
));

//echo "added $id ", $item['name'], ' ', $item['email'], '<br>';
}

        $db->query("update $db->comments set tmp = '$id' where author= '" . $item['id'] . "'");
$map[(int) $item['id']] = (int) $id;
      }

$db->table = 'comusers';
    }

//create temp table
$man->createtable('tempsubscribers', file_get_contents(litepublisher::$paths->lib . 'install' .DIRECTORY_SEPARATOR . 'items.posts.sql'));
    $from = 0;
$db->table = 'subscribers';
    while ($items = $db->res2assoc($db->query("select * from $db->subscribers limit $from, 500"))) {
$from += count($items);
$db->table = 'tempsubscribers';
        $vals = array();
      foreach ($items as $item) {
          $vals[]= sprintf('(%d, %s)', $item['post'], $map[$item['item']]);
        }
        $db->exec("INSERT INTO $db->tempsubscribers (post, item) values " . implode(',', $vals) );
$db->table = 'subscribers';
}

$man->deletetable('subscribers');
$db->query("rename table $db->tempsubscribers to $db->subscribers");

//move column
$man->alter('comments', "drop index author");
$man->alter('comments', "drop author");
$man->alter('comments', "change tmp author int unsigned NOT NULL default '0'");
$man->alter('comments', "add KEY `author` (`author`)");

$man->deletetable('comusers');
$man->deletetable('commentskept');
}