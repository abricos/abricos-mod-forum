<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current;
$db = Abricos::$db;
$pfx = $db->prefix;

$uprofileManager = Abricos::GetModule('uprofile')->GetManager();

if ($updateManager->isInstall()){

    $uprofileManager->FieldAppend('lastname', 'Фамилия', UserFieldType::STRING, 100);
    $uprofileManager->FieldAppend('firstname', 'Имя', UserFieldType::STRING, 100);
    $uprofileManager->FieldCacheClear();

    Abricos::GetModule('forum')->permission->Install();

    // проекты
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."frm_topic (
			topicid int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор сообщения',
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			pubkey varchar(32) NOT NULL DEFAULT '' COMMENT 'Уникальный публичный ключ',
			isprivate tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Приватная запись',
			userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор автора',
			forumid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор форума',
			title varchar(250) NOT NULL DEFAULT '' COMMENT 'Название',
			contentid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор контента сообщения',
			
			status int(2) unsigned NOT NULL DEFAULT 0 COMMENT 'Текущий статус записи',
			statuserid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь текущего статуса',
			statdate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время текущего статуса',
			
			cmtcount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во комменатрий',
			cmtuserid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь последнего комментария',
			cmtdate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время последнего комментария',

			dateline int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время создания',
			upddate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время обновления',
			deldate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время удаления',
			
			PRIMARY KEY  (topicid),
			KEY (language, deldate)
		)".$charset);

    // Прикрепленные файлы к сообщению
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."frm_file (
		  fileid int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор',
		  topicid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор сообщения',
		  userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя',
		  filehash varchar(8) NOT NULL DEFAULT '' COMMENT 'Идентификатор файла таблицы fm_file',
		  PRIMARY KEY  (fileid), 
		  UNIQUE KEY file (topicid,filehash)
		)".$charset);

}

if ($updateManager->isUpdate('0.1.3') && !$updateManager->isInstall()){

    $db->query_write("
		ALTER TABLE ".$pfx."frm_message
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык'
	");
    $db->query_write("UPDATE ".$pfx."frm_message SET language='ru'");

}

if ($updateManager->isUpdate('0.1.5') && !$updateManager->isInstall()){
    $db->query_write("
		DROP TABLE IF EXISTS ".$pfx."frm_forum
	");

    $db->query_write("
		ALTER TABLE ".$pfx."frm_message
		ADD forumid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор форума',
		ADD deldate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время удаления',
		ADD KEY (language, deldate)
	");

}
if ($updateManager->isUpdate('0.1.5')){

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."frm_forum (
			forumid int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор',
			parentforumid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Родитель',
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			forumtype int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Тип форума: 0-категория, 1-форум',
			
			title varchar(250) NOT NULL DEFAULT '' COMMENT 'Название',
			descript TEXT NOT NULL COMMENT 'Описание',
			
			dateline int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время создания',
			upddate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время обновления',
			deldate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата/время удаления',
			
			PRIMARY KEY  (forumid)
		)".$charset);

}

if ($updateManager->isUpdate('0.1.6') && !$updateManager->isInstall()){
    $db->query_write("
		RENAME TABLE ".$pfx."frm_message TO ".$pfx."frm_topic
	");

    $db->query_write("
		ALTER TABLE ".$pfx."frm_topic
		CHANGE messageid topicid int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор сообщения'
	");

    $db->query_write("
		ALTER TABLE ".$pfx."frm_file
		CHANGE messageid topicid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор сообщения'
	");

}
?>