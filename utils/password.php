<?php
define('litepublisher_mode', 'xmlrpc');
include('index.php');
   $password = md5uniq();
   litepublisher::$options->SetPassword($password);
litepublisher::$options->savemodified();
echo "<pre>\n";
echo "$password\n<br>new password";
?>