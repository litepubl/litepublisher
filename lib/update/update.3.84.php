<?php
function update384() {
if (dbversion) {
$data = &litepublisher::$options->data;
$data['storage'] = array();
$storage = &$data['storage'];
foreach (array('posts', 'users', 'urlmap', 'cron', 'comments', 'tags', 'categories') as $name) {
if (isset($data[$name])) {
$storage[$name] = $data[$name];
unset($data[$name]);
}
}
litepublisher::$options->savemodified();
}
}
?>