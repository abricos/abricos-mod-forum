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

    public static function TopicAppend(ForumApp $app, ForumTopic $topic){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."forum_topic (
				userid, title, pubkey, body, isprivate, dateline, upddate, language) VALUES (
				".intval(Abricos::$user->id).",
				'".bkstr($topic->title)."',
				'".bkstr($topic->pubkey)."',
				'".bkstr($topic->body)."',
				0,
				".TIMENOW.",
				".TIMENOW.",
				'".bkstr(Abricos::$LNG)."'
			)
		";
        $db->query_write($sql);
        $topicid = $db->insert_id();
        ForumQuery::TopicStatusUpdate($app, $topicid, ForumTopic::OPENED);
        return $topicid;
    }

    public static function TopicSetNotifyOwnerId(ForumApp $app, $topicid, $ownerid){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."forum_topic
			SET notifyOwnerId=".intval($ownerid)."
			WHERE topicid=".intval($topicid)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function TopicUpdate(ForumApp $app, ForumTopic $topic){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."forum_topic
			SET
				title='".bkstr($topic->title)."',
				body='".bkstr($topic->body)."'
			WHERE topicid=".intval($topic->id)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function Topic(ForumApp $app, $topicid){
        $db = $app->db;
        $sql = "
			SELECT t.*
			FROM ".$db->prefix."forum_topic t
			INNER JOIN ".$db->prefix."forum_topicstatus s ON t.statusid=s.statusid
			WHERE language='".bkstr(Abricos::$LNG)."' AND t.topicid=".intval($topicid)."
		";
        if (!$app->manager->IsModerRole()){
            // приватные темы доступны только авторам и модераторам
            $sql .= "
				AND (t.isprivate=0 OR (t.isprivate=1 AND t.userid=".intval(Abricos::$user->id)."))
				AND s.status<>'".ForumTopic::REMOVED."'
			";
        }

        $sql .= " LIMIT 1 ";

        return $db->query_first($sql);
    }

    public static function TopicListByIds(ForumApp $app, $topicids){
        $count = count($topicids);
        if ($count === 0){
            return;
        }
        $db = $app->db;
        $wheres = array();
        for ($i = 0; $i < $count; $i++){
            $wheres[] = "t.topicid=".intval($topicids[$i]);
        }

        $sql = "
			SELECT
			  t.*,
			  '' as body
			FROM ".$db->prefix."forum_topic t
			INNER JOIN ".$db->prefix."forum_topicstatus s ON t.statusid=s.statusid
			WHERE t.language='".bkstr(Abricos::$LNG)."'
		";
        if (!$app->manager->IsModerRole()){
            // приватные темы доступны только авторам и модераторам
            $sql .= "
				AND (t.isprivate=0 OR (t.isprivate=1 AND t.userid=".intval(Abricos::$user->id)."))
				AND s.status<>'".ForumTopic::REMOVED."'
			";
        }

        $sql .= "
            AND (".implode(" OR ", $wheres).")
        ";

        $sql .= "
			ORDER BY t.upddate DESC
			LIMIT 300
		";

        return $db->query_read($sql);
    }


    public static function TopicList(ForumApp $app, $page = 1, $limit = 20){
        $db = $app->db;
        $page = intval($page);
        $limit = intval($limit);
        $from = $limit * (max($page, 1) - 1);

        $sql = "
			SELECT
			  t.*,
			  '' as body
			FROM ".$db->prefix."forum_topic t
			INNER JOIN ".$db->prefix."forum_topicstatus s ON t.statusid=s.statusid
			WHERE t.language='".bkstr(Abricos::$LNG)."'
		";
        if (!$app->manager->IsModerRole()){
            // приватные темы доступны только авторам и модераторам
            $sql .= "
				AND (t.isprivate=0 OR (t.isprivate=1 AND t.userid=".intval(Abricos::$user->id)."))
				AND s.status<>'".ForumTopic::REMOVED."'
			";
        }

        $sql .= "
			ORDER BY t.upddate DESC
			LIMIT ".$from.",".intval($limit)."
		";

        return $db->query_read($sql);
    }

    public static function TopicStatusList(ForumApp $app, $topicid){
        $db = $app->db;
        $sql = "
			SELECT s.*
			FROM ".$db->prefix."forum_topicstatus s
			WHERE topicid=".intval($topicid)."
			ORDER BY dateline DESC
		";
        return $db->query_read($sql);
    }

    public static function TopicStatusListByIds(ForumApp $app, $statusids){
        $db = $app->db;
        $aw = array();
        $count = count($statusids);
        if ($count === 0){
            return null;
        }

        for ($i = 0; $i < $count; $i++){
            $aw[] = "s.statusid=".intval($statusids[$i]);
        }

        $sql = "
			SELECT s.*
			FROM ".$db->prefix."forum_topicstatus s
			WHERE ".implode(" OR ", $aw)."
		";
        return $db->query_read($sql);
    }

    public static function TopicStatusUpdate(ForumApp $app, $topicid, $status){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."forum_topicstatus
			(topicid, status, userid, dateline) VALUES (
			    ".intval($topicid).",
				'".bkstr($status)."',
				".intval(Abricos::$user->id).",
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        $statusid = $db->insert_id();
        $sql = "
            UPDATE ".$db->prefix."forum_topic
            SET statusid=".intval($statusid)."
            WHERE topicid=".intval($topicid)."
            LIMIT 1
		";
        $db->query_write($sql);
        return $statusid;
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
     * @param array|integer $topicids
     */
    public static function TopicFileList(ForumApp $app, $topicids){
        $db = $app->db;
        if (!is_array($topicids)){
            $topicids = array(intval($topicids));
        }
        $aWh = array();
        foreach ($topicids as $tid){
            array_push($aWh, "bf.topicid=".intval($tid));
        }

        $sql = "
			SELECT 
				bf.fileid,
				bf.topicid,
				f.filehash,
				f.filename,
				f.filesize,
				f.counter,
				f.dateline
			FROM ".$db->prefix."forum_topicfile bf
			INNER JOIN ".$db->prefix."fm_file f ON bf.filehash=f.filehash
			WHERE ".implode(" OR ", $aWh)."
			ORDER BY topicid
		";
        return $db->query_read($sql);
    }

    public static function TopicFileAppend(ForumApp $app, $topicid, $filehash, $userid){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."forum_topicfile (topicid, filehash, userid) VALUES
			(
				".intval($topicid).",
				'".bkstr($filehash)."',
				".intval($userid)."
			)
		";
        $db->query_write($sql);
    }

    public static function TopicFilesRemove(ForumApp $app, $topicid){
        $db = $app->db;
        $sql = "
			DELETE FROM ".$db->prefix."forum_topicfile
			WHERE topicid=".intval($topicid)."
		";
        $db->query_write($sql);
    }

    public static function TopicViewCountUpdate(ForumApp $app, ForumTopic $topic){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."forum_topic
			SET viewcount=viewcount+1
			WHERE topicid=".intval($topic->id)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function TopicCommentStatisticUpdate(ForumApp $app, ForumTopic $topic, CommentStatistic $statistic){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."forum_topic
			SET upddate=".TIMENOW."
			WHERE topicid=".intval($topic->id)."
		";
        $db->query_write($sql);
    }

}
