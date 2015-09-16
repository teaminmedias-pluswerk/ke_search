#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_kesearch_tags text
);



#
# Table structure for table 'tx_kesearch_filters'
#
CREATE TABLE tx_kesearch_filters (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title tinytext,
	options text,
	rendertype varchar(90) DEFAULT '' NOT NULL,
	expandbydefault tinyint(1) DEFAULT '0' NOT NULL,
	markAllCheckboxes tinyint(1) DEFAULT '0' NOT NULL,
	cssclass varchar(90) DEFAULT '' NOT NULL,
	target_pid int(11) DEFAULT '0' NOT NULL,
	amount int(11) DEFAULT '0' NOT NULL,
	shownumberofresults tinyint(1) DEFAULT '0' NOT NULL,
	alphabeticalsorting tinyint(1) DEFAULT '0' NOT NULL,
	wrap tinytext,

	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE=MyISAM;



#
# Table structure for table 'tx_kesearch_filteroptions'
#
CREATE TABLE tx_kesearch_filteroptions (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title tinytext,
	tag tinytext,
	automated_tagging text,
	automated_tagging_exclude text,
	sorting int(11) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE=MyISAM;



#
# Table structure for table 'tx_kesearch_index'
#
CREATE TABLE tx_kesearch_index (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group varchar(100) DEFAULT '0' NOT NULL,
	targetpid text,
	content mediumtext,
	params tinytext,
	type tinytext,
	tags text,
	abstract text,
	sortdate int(11) DEFAULT '0' NOT NULL,
	orig_uid int(11) DEFAULT '0' NOT NULL,
	orig_pid int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	language int(11) DEFAULT '0' NOT NULL,
	directory tinytext,
	hash varchar(32) DEFAULT '' NOT NULL,

	FULLTEXT INDEX tags (tags),
	FULLTEXT INDEX title (title),
	FULLTEXT INDEX titlecontent (title,content),

	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE=MyISAM;



#
# Table structure for table 'tx_kesearch_indexerconfig'
#
CREATE TABLE tx_kesearch_indexerconfig (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title tinytext,
	storagepid text,
	targetpid text,
	startingpoints_recursive text,
	single_pages text,
	sysfolder text,
	index_content_with_restrictions text,
	index_passed_events text,
	type varchar(90) DEFAULT '' NOT NULL,
	index_news_category_mode tinyint(4) DEFAULT '0' NOT NULL,
	index_news_category_selection text,
	index_extnews_category_selection text,
	index_news_useHRDatesSingle tinyint(4) DEFAULT '0' NOT NULL,
	index_news_useHRDatesSingleWithoutDay tinyint(4) DEFAULT '0' NOT NULL,
	index_dam_categories text,
	index_dam_without_categories tinyint(4) DEFAULT '0' NOT NULL,
	index_dam_categories_recursive tinyint(3) DEFAULT '0' NOT NULL,
	index_use_page_tags tinyint(3) DEFAULT '0' NOT NULL,
	index_use_page_tags_for_files tinyint(3) DEFAULT '0' NOT NULL,
	directories text,
	fileext tinytext,
	commenttypes tinytext,
	filteroption int(11) DEFAULT '0' NOT NULL,
	tvpath varchar(255) DEFAULT '' NOT NULL,
	fal_storage int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE=MyISAM;

#
# Table structure for table 'tx_kesearch_stat_search'
#
CREATE TABLE tx_kesearch_stat_search (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  searchphrase text,
  tstamp int(11) DEFAULT '0' NOT NULL,
  hits int(11) DEFAULT '0' NOT NULL,
  tagsagainst text,
  language int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid)
) ENGINE=MyISAM;

#
# Table structure for table 'tx_kesearch_stat_word'
#
CREATE TABLE tx_kesearch_stat_word (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  word text,
  tstamp int(11) DEFAULT '0' NOT NULL,
  pageid int(11) DEFAULT '0' NOT NULL,
  resultsfound int(1) DEFAULT '0' NOT NULL,
  language int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
) ENGINE=MyISAM;
