<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class ForumQuery
 */
class ForumQuery {

    public static function TopicAppend(ForumApp $app, $d, $pubkey){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."forum_topic (
				userid, title, pubkey, body, isprivate, status, dateline, upddate, language) VALUES (
				".bkint(Abricos::$user->id).",
				'".bkstr($d->title)."',
				'".bkstr($pubkey)."',
				'".bkstr($d->body)."',
				".bkint($d->isprivate).",
				".Forum::ST_OPENED.",
				".TIMENOW.",
				".TIMENOW.",
				'".bkstr(Abricos::$LNG)."'
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function TopicUpdate(ForumApp $app, ForumTopic $topic, $d, $userid){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."forum_topic
			SET
				title='".bkstr($d->title)."',
				body='".bkstr($d->body)."',
				upddate=".TIMENOW."
			WHERE topicid=".bkint($d->id)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function Topic(ForumApp $app, $topicid){
        $db = $app->db;
        $sql = "
			SELECT t.*
			FROM ".$db->prefix."forum_topic t
			WHERE language='".bkstr(Abricos::$LNG)."'
			    AND topicid=".intval($topicid)."
		";
        if (!$app->manager->IsModerRole()){
            // приватные темы доступны только авторам и модераторам
            $sql .= "
				AND (t.isprivate=0 OR (t.isprivate=1 AND t.userid=".bkint(Abricos::$user->id)."))
				AND t.status != ".ForumTopic::ST_REMOVED."
			";
        }

        $sql .= " LIMIT 1 ";

        return $db->query_first($sql);
    }

    public static function TopicList(ForumApp $app, $page = 1, $limit = 20){
        $db = $app->db;
        $page = intval($page);
        $limit = intval($limit);
        $from = $limit * (max($page, 1) - 1);

        $sql = "
			SELECT t.*
			FROM ".$db->prefix."forum_topic t
			WHERE t.language='".bkstr(Abricos::$LNG)."'
		";
        if (!$app->manager->IsModerRole()){
            // приватные темы доступны только авторам и модераторам
            $sql .= "
				AND (t.isprivate=0 OR (t.isprivate=1 AND t.userid=".bkint(Abricos::$user->id)."))
				AND t.status != ".ForumTopic::ST_REMOVED."
			";
        }

        $sql .= "
			ORDER BY t.upddate DESC
			LIMIT ".$from.",".bkint($limit)."
		";

        return $db->query_read($sql);
    }

    public static function ModeratorList(ForumApp $app){
        $db = $app->db;
        $sql = "
			SELECT 
				u.userid as id,
				u.username as unm,
				u.lastname as lnm,
				u.firstname as fnm,
				u.email as eml
			FROM ".$db->prefix."usergroup ug
			LEFT JOIN ".$db->prefix."group g ON g.groupid = ug.groupid
			LEFT JOIN ".$db->prefix."user u ON ug.userid = u.userid
			WHERE g.groupkey='".ForumGroup::MODERATOR."'
		";
        return $db->query_read($sql);
    }

    /**
     * Список файлов топика
     *
     * @param Ab_Database $db
     * @param array|integer $tids
     */
    public static function TopicFileList(ForumApp $app, $tids){
        $db = $app->db;
        if (!is_array($tids)){
            $tids = array(intval($tids));
        }
        $aWh = array();
        foreach ($tids as $tid){
            array_push($aWh, "bf.topicid=".bkint($tid));
        }

        $sql = "
			SELECT 
				bf.filehash as id,
				bf.topicid as tid,
				f.filename as fn,
				f.filesize as sz,
				f.counter as cnt,
				f.dateline as dl
			FROM ".$db->prefix."forum_topicfile bf
			INNER JOIN ".$db->prefix."fm_file f ON bf.filehash=f.filehash
			WHERE ".implode(" OR ", $aWh)."
			ORDER BY tid
		";
        return $db->query_read($sql);
    }

    public static function TopicFileAppend(ForumApp $app, $topicid, $filehash, $userid){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."forum_topicfile (topicid, filehash, userid) VALUES
			(
				".bkint($topicid).",
				'".bkstr($filehash)."',
				".bkint($userid)."
			)
		";
        $db->query_write($sql);
    }

    public static function TopicFileRemove(ForumApp $app, $topicid, $filehash){
        $db = $app->db;
        $sql = "
			DELETE FROM ".$db->prefix."forum_topicfile
			WHERE topicid=".bkint($topicid)." AND filehash='".bkstr($filehash)."' 
		";
        $db->query_write($sql);
    }

    public static function TopicSetStatus(ForumApp $app, $topicid, $status, $userid){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."forum_topic
			SET
				status=".bkint($status).",
				statuserid=".bkint($userid).",
				statdate=".TIMENOW."
			WHERE topicid=".bkint($topicid)."
		";
        $db->query_write($sql);
    }

}

?>