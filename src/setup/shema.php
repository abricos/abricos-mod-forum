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

if ($updateManager->isInstall()){

    Abricos::GetModule('forum')->permission->Install();
    /*
        $db->query_write("
            CREATE TABLE IF NOT EXISTS ".$pfx."frm_topic (
                topicid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор сообщения',
                language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
                pubkey VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'Уникальный публичный ключ',
                isprivate TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Приватная запись',
                userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор автора',
                forumid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор форума',
                title VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Название',
                contentid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор контента сообщения',

                status INT(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Текущий статус записи',
                statuserid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователь текущего статуса',
                statdate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время текущего статуса',

                cmtcount INT(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во комменатрий',
                cmtuserid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователь последнего комментария',
                cmtdate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время последнего комментария',

                dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время создания',
                upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время обновления',
                deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время удаления',

                PRIMARY KEY (topicid),
                KEY (language, deldate)
            )".$charset);

        // Прикрепленные файлы к сообщению
        $db->query_write("
            CREATE TABLE IF NOT EXISTS ".$pfx."frm_file (
              fileid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор',
              topicid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор сообщения',
              userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя',
              filehash VARCHAR(8) NOT NULL DEFAULT '' COMMENT 'Идентификатор файла таблицы fm_file',
              PRIMARY KEY (fileid),
              UNIQUE KEY file (topicid,filehash)
            )".$charset);
    /**/
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
		ADD forumid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор форума',
		ADD deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время удаления',
		ADD KEY (language, deldate)
	");
}

if ($updateManager->isUpdate('0.1.6') && !$updateManager->isInstall()){
    $db->query_write("
		RENAME TABLE ".$pfx."frm_message TO ".$pfx."frm_topic
	");

    $db->query_write("
		ALTER TABLE ".$pfx."frm_topic
		CHANGE messageid topicid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор сообщения'
	");

    $db->query_write("
		ALTER TABLE ".$pfx."frm_file
		CHANGE messageid topicid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор сообщения'
	");
}

if ($updateManager->isUpdate('0.1.8')){

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."forum_topic (
            topicid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор сообщения',
            statusid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор автора',

            userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор автора',
            dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            isprivate TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Приватная запись',
            title VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Название',
            body text NOT NULL COMMENT 'Запись топика',

            pubkey VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'Уникальный публичный ключ',

			viewcount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            notifyOwnerId INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время обновления',

            language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',

            PRIMARY KEY (topicid),
            KEY isprivate (isprivate),
            KEY upddate (upddate),
            KEY language (language)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."forum_topicstatus (
            statusid INT(10) UNSIGNED NOT NULL auto_increment COMMENT '',

            topicid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            status ENUM('opened', 'closed', 'removed') NOT NULL COMMENT '',
            userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            PRIMARY KEY (statusid),
            KEY topicid (topicid)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."forum_topicfile (
            fileid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор',
            topicid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор сообщения',
            userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя',
            filehash VARCHAR(8) NOT NULL DEFAULT '' COMMENT 'Идентификатор файла таблицы fm_file',
            PRIMARY KEY (fileid),
            UNIQUE KEY file (topicid, filehash)
        )".$charset
    );

    /** @var NotifyManager $notifyManager */
    $notifyManager = Abricos::GetModule('notify')->GetManager();
    $notifyManager->RolesDisable();
    $notifyOwnerApp = $notifyManager->GetApp()->Owner();

    // Module Owner
    $ownerForumId = $notifyOwnerApp->BaseAppend(array(
        'recordType' => NotifyOwner::TYPE_MODULE,
        'module' => 'forum',
        'status' => NotifyOwner::STATUS_ON,
        'defaultStatus' => NotifySubscribe::STATUS_ON,
        'defaultEmailStatus' => NotifySubscribe::EML_STATUS_PARENT,
    ));

    // Topic Container Owner
    $ownerForumTopicId = $notifyOwnerApp->BaseAppend(array(
        'recordType' => NotifyOwner::TYPE_CONTAINER,
        'parentid' => $ownerForumId,
        'module' => 'forum',
        'type' => 'topic',
        'status' => NotifyOwner::STATUS_ON
    ));

    // Topic New Method Owner
    $notifyOwnerApp->BaseAppend(array(
        'parentid' => $ownerForumTopicId,
        'recordType' => NotifyOwner::TYPE_METHOD,
        'module' => 'forum',
        'type' => 'topic',
        'method' => 'new',
        'status' => NotifyOwner::STATUS_ON,
        'defaultStatus' => NotifySubscribe::STATUS_OFF,
        'defaultEmailStatus' => NotifySubscribe::EML_STATUS_PARENT,
        'isChildSubscribe' => true,
        'eventTimeout' => 60 * 10
    ));

    // Topic Change Method Owner
    $ownerTopicChangeId = $notifyOwnerApp->BaseAppend(array(
        'parentid' => $ownerForumTopicId,
        'recordType' => NotifyOwner::TYPE_METHOD,
        'module' => 'forum',
        'type' => 'topic',
        'method' => 'change',
        'status' => NotifyOwner::STATUS_ON,
        'defaultStatus' => NotifySubscribe::STATUS_ON,
        'defaultEmailStatus' => NotifySubscribe::EML_STATUS_ALWAYS,
    ));

    // Topic Comment Method Owner
    $ownerTopicCommentId = $notifyOwnerApp->BaseAppend(array(
        'parentid' => $ownerForumTopicId,
        'recordType' => NotifyOwner::TYPE_METHOD,
        'module' => 'forum',
        'type' => 'topic',
        'method' => 'comment',
        'status' => NotifyOwner::STATUS_ON,
        'defaultStatus' => NotifySubscribe::STATUS_ON,
        'defaultEmailStatus' => NotifySubscribe::EML_STATUS_FIRST,
    ));

}

if ($updateManager->isUpdate('0.1.8') && !$updateManager->isInstall()){
    /* // prev version table

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."frm_topic (
			topicid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор сообщения',
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			pubkey VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'Уникальный публичный ключ',
			isprivate TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Приватная запись',
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор автора',
			forumid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор форума',
			title VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Название',
			contentid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор контента сообщения',

			status INT(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Текущий статус записи',
			statuserid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователь текущего статуса',
			statdate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время текущего статуса',

			cmtcount INT(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во комменатрий',
			cmtuserid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователь последнего комментария',
			cmtdate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время последнего комментария',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время создания',
			upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время обновления',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата/время удаления',

			PRIMARY KEY (topicid),
			KEY (language, deldate)
		)".$charset);

    // Прикрепленные файлы к сообщению
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."frm_file (
		  fileid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор',
		  topicid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор сообщения',
		  userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя',
		  filehash VARCHAR(8) NOT NULL DEFAULT '' COMMENT 'Идентификатор файла таблицы fm_file',
		  PRIMARY KEY (fileid),
		  UNIQUE KEY file (topicid,filehash)
		)".$charset);
    /**/

    $db->query_write("
		INSERT INTO ".$pfx."forum_topicfile
		(fileid, topicid, userid, filehash)
		SELECT fileid, topicid, userid, filehash
		FROM ".$pfx."frm_file
	");


    $db->query_write("
		INSERT INTO ".$pfx."forum_topicstatus (
		    topicid, status, userid, dateline
		)
		SELECT
		    t.topicid, 'opened', t.userid, t.dateline
		FROM ".$pfx."frm_topic t
	");

    $db->query_write("
		INSERT INTO ".$pfx."forum_topicstatus (
		    topicid, status, userid, dateline
		)
		SELECT
		    t.topicid, IF(t.status=1, 'closed', 'removed'), t.userid, t.upddate
		FROM ".$pfx."frm_topic t
		WHERE t.status>0
	");

    $db->query_write("
		INSERT INTO ".$pfx."forum_topic (
		    topicid, userid, statusid,
		    isprivate, title, body,
		    pubkey,
		    language, dateline, upddate
		)
		SELECT
		    t.topicid, t.userid,
		    (
		        SELECT MAX(s.statusid)
                FROM ".$pfx."forum_topicstatus s
                WHERE s.topicid=t.topicid
            ) as statusid,
		    t.isprivate, t.title, c.body,
		    t.pubkey,
		    t.language, t.dateline, t.upddate
		FROM ".$pfx."frm_topic t
		INNER JOIN ".$pfx."content c ON c.contentid=t.contentid
	");

    $db->query_write("
		UPDATE ".$pfx."comment_owner o
		INNER JOIN ".$pfx."frm_topic t ON t.contentid=o.ownerid
		    AND o.ownerModule='forum' AND o.ownerType='content'
		SET
		    o.ownerid=t.topicid,
		    o.ownerType='topic'
	");

    $db->query_write("
		UPDATE ".$pfx."comment_ownerstat o
		INNER JOIN ".$pfx."frm_topic t ON t.contentid=o.ownerid
		    AND o.ownerModule='forum' AND o.ownerType='content'
		SET
		    o.ownerid=t.topicid,
		    o.ownerType='topic'
	");

    $db->query_write("DELETE FROM ".$pfx."content WHERE modman='forum'");

    $db->query_write("DROP TABLE IF EXISTS ".$pfx."frm_forum");
    $db->query_write("DROP TABLE IF EXISTS ".$pfx."frm_topic");
    $db->query_write("DROP TABLE IF EXISTS ".$pfx."frm_file");

    $db->query_write("
		INSERT INTO ".$pfx."notify_owner (
		    parentid, ownerModule, ownerType, ownerMethod, ownerItemId, ownerStatus
		)
		SELECT
		    ".intval($ownerTopicCommentId)." as parentid,
		    'forum' as ownerModule,
		    'topic' as ownerType,
		    'comment' as ownerMethod,
		    topicid,
		    'on' as ownerStatus
		FROM ".$pfx."forum_topic
	");

    $db->query_write("
		INSERT INTO ".$pfx."notify_subscribe (
		    ownerid, userid, status, emailStatus, dateline
		)
		SELECT
		    o.ownerid,
		    t.userid,
		    'on' as status,
		    'on' as emailStatus,
		    t.dateline
		FROM ".$pfx."forum_topic t
		INNER JOIN ".$pfx."notify_owner o
		    ON o.ownerModule='forum' AND o.ownerType='topic' AND o.ownerMethod='comment'
		    AND o.ownerItemId=t.topicid
	");

    $db->query_write("
        UPDATE ".$pfx."forum_topic t
        INNER JOIN ".$pfx."notify_owner o ON o.ownerModule='forum' AND o.ownerType='topic'
            AND o.ownerMethod='comment' AND o.ownerItemId=t.topicid
        SET notifyOwnerId=o.ownerid
	");

}
