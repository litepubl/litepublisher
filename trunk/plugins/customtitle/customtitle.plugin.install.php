<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function tcustomtitleInstall($self) {
  $template = ttemplate::i();
  $template->ontitle = $self->ontitle;
}

function tcustomtitleUninstall($self) {
  $template = ttemplate::i();
  $template->unbind($self);
}