  `id` int unsigned NOT NULL default '0',
  `idurl` int unsigned NOT NULL default '0',
  `idview` int unsigned NOT NULL default '1',
  `avatar` int(10) unsigned NOT NULL default '0',
  `name` text not null,
  `email` varchar(64) NOT NULL,
  `website` varchar(255) NOT NULL,
  `ip` varchar(15) NOT NULL default '',
  `registered` datetime NOT NULL default '2010-01-01 10:01:01',
  `content` text NOT NULL,
  `rawcontent` text NOT NULL,
  `keywords` text NOT NULL,
  `description` text NOT NULL,
  `head` text NOT NULL,

  KEY `id` (`id`)