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

    public static function TopicAppend(Ab_Database $db, $d, $pubkey){
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

    public static function TopicUpdate(Ab_Database $db, ForumTopic $topic, $d, $userid){
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

    public static function Topic(Ab_Database $db, $topicid){
        $sql = "
			SELECT t.*
			FROM ".$db->prefix."forum_topic t
			WHERE language='".bkstr(Abricos::$LNG)."'
			    AND topicid=".intval($topicid)."
		";
        if (!ForumManager::$instance->IsModerRole()){
            // приватные темы доступны только авторам и модераторам
            $sql .= "
				AND (t.isprivate=0 OR (t.isprivate=1 AND mtuserid=".bkint(Abricos::$user->id)."))
				AND t.status != ".ForumTopic::ST_REMOVED."
			";
        }

        $sql .= "
        	LIMIT 1
		";

        return $db->query_first($sql);
    }

    public static function TopicList(Ab_Database $db, $page = 1, $limit = 20){
        $page = intval($page);
        $limit = intval($limit);
        $from = $limit * (max($page, 1) - 1);

        $sql = "
			SELECT
                t.topicid,
                t.userid,
                t.title,
                t.status,
                t.statuserid,
                t.statdate,
                t.isprivate,
                t.dateline,
                t.upddate
			FROM ".$db->prefix."forum_topic t
			WHERE t.language='".bkstr(Abricos::$LNG)."'
		";
        if (!ForumManager::$instance->IsModerRole()){
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

    public static function CommentList(Ab_Database $db, $userid){
        $sql = "
			SELECT 
				a.commentid as id,
				a.parentcommentid as pid,
				t1.topicid as tkid,
				a.body as bd, 
				a.dateedit as de,
				a.status as st, 
				u.userid as uid, 
				u.username as unm,
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm
			FROM ".$db->prefix."cmt_comment a 
			INNER JOIN (SELECT
					m.topicid, 
					m.contentid
				FROM ".$db->prefix."forum_topic m 
				WHERE (m.isprivate=0 OR (m.isprivate=1 AND m.userid=".bkint($userid).")) AND m.language='".bkstr(Abricos::$LNG)."'
			) t1 ON t1.contentid=a.contentid
			LEFT JOIN ".$db->prefix."user u ON u.userid = a.userid
			ORDER BY a.commentid DESC  
			LIMIT 15
		";
        return $db->query_read($sql);
    }

    public static function ModeratorList(Ab_Database $db){
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
    public static function TopicFileList(Ab_Database $db, $tids){
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
			FROM ".$db->prefix."frm_file bf
			INNER JOIN ".$db->prefix."fm_file f ON bf.filehash=f.filehash
			WHERE ".implode(" OR ", $aWh)."
			ORDER BY tid
		";
        return $db->query_read($sql);
    }

    public static function TopicFileAppend(Ab_Database $db, $topicid, $filehash, $userid){
        $sql = "
			INSERT INTO ".$db->prefix."frm_file (topicid, filehash, userid) VALUES
			(
				".bkint($topicid).",
				'".bkstr($filehash)."',
				".bkint($userid)."
			)
		";
        $db->query_write($sql);
    }

    public static function TopicFileRemove(Ab_Database $db, $topicid, $filehash){
        $sql = "
			DELETE FROM ".$db->prefix."frm_file
			WHERE topicid=".bkint($topicid)." AND filehash='".bkstr($filehash)."' 
		";
        $db->query_write($sql);
    }

    public static function TopicSetStatus(Ab_Database $db, $topicid, $status, $userid){
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


    public static function MyUserData(Ab_Database $db, $userid, $retarray = false){
        $sql = "
			SELECT
				DISTINCT
				u.userid as id,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm,
				u.avatar as avt
			FROM ".$db->prefix."user u 
			WHERE u.userid=".bkint($userid)."
			LIMIT 1
		";
        return $retarray ? $db->query_first($sql) : $db->query_read($sql);
    }

    /*
        public static function TopicUnsetStatus(Ab_Database $db, $topicid){
            $sql = "
                UPDATE ".$db->prefix."forum_topic
                SET status=".ForumTopic::ST_DRAW_OPEN.", statuserid=0, statdate=0
                WHERE topicid=".bkint($topicid)."
            ";
            $db->query_write($sql);
        }
        /**/

    /**
     * Список участников проекта
     *
     * @param Ab_Database $db
     * @param integer $topicid
     */
    public static function TopicUserList(Ab_Database $db, $topicid){
        $sql = "
			SELECT 
				p.userid as id,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm
			FROM ".$db->prefix."frm_userrole p
			INNER JOIN ".$db->prefix."user u ON p.userid=u.userid
			WHERE p.topicid=".bkint($topicid)."
		";
        return $db->query_read($sql);
    }

    /**
     * Список участников проекта с расшириными полями для служебных целей (отправка уведомлений и т.п.)
     *
     * @param Ab_Database $db
     * @param integer $topicid
     */
    public static function TopicUserListForNotify(Ab_Database $db, $topicid){
        $sql = "
			SELECT 
				p.userid as id,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm,
				u.email
				FROM ".$db->prefix."frm_userrole p
			INNER JOIN ".$db->prefix."user u ON p.userid=u.userid
			WHERE p.topicid=".bkint($topicid)."
		";
        return $db->query_read($sql);
    }


}

?>