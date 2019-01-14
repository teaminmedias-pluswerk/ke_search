#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_kesearch_tags text,
	tx_kesearch_abstract text,
	tx_kesearch_resultimage int(11) unsigned DEFAULT '0' NOT NULL
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
	markAllCheckboxes tinyint(1) DEFAULT '0' NOT NULL,
	target_pid int(11) DEFAULT '0' NOT NULL,
	amount int(11) DEFAULT '0' NOT NULL,
	shownumberofresults tinyint(1) DEFAULT '0' NOT NULL,
	alphabeticalsorting tinyint(1) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);



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
);



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
	orig_uid varchar(255) DEFAULT '0' NOT NULL,
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
) ENGINE = MyISAM;



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
	type varchar(90) DEFAULT '' NOT NULL,
	index_news_category_mode tinyint(4) DEFAULT '0' NOT NULL,
	index_news_category_selection text,
	index_extnews_category_selection text,
	index_news_archived tinyint(4) DEFAULT '0' NOT NULL,
	index_news_useHRDatesSingle tinyint(4) DEFAULT '0' NOT NULL,
	index_news_useHRDatesSingleWithoutDay tinyint(4) DEFAULT '0' NOT NULL,
	index_use_page_tags tinyint(3) DEFAULT '0' NOT NULL,
	index_use_page_tags_for_files tinyint(3) DEFAULT '0' NOT NULL,
	index_page_doctypes varchar(255) DEFAULT '' NOT NULL,
	directories text,
	fileext tinytext,
	filteroption int(11) DEFAULT '0' NOT NULL,
	tvpath varchar(255) DEFAULT '' NOT NULL,
	fal_storage int(11) DEFAULT '0' NOT NULL,
	contenttypes text,
	cal_expired_events tinyint(3) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

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
);

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
  PRIMARY KEY (uid)
);
