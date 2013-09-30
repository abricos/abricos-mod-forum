<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class ForumQuery {
	
	public static function ForumAppend(Ab_Database $db, $d){
		$sql = "
			INSERT INTO ".$db->prefix."frm_forum 
				(title, descript, dateline, upddate, language) VALUES (
				'".bkstr($d->tl)."',
				'".bkstr($d->tl)."',
				".TIMENOW.",
				".TIMENOW.",
				'".bkstr(Abricos::$LNG)."'
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function TopicAppend(Ab_Database $db, $msg, $pubkey){
		$contentid = Ab_CoreQuery::ContentAppend($db, $msg->bd, 'forum');
		
		$sql = "
			INSERT INTO ".$db->prefix."frm_topic (
				userid, title, pubkey, contentid, isprivate, status, dateline, upddate, language) VALUES (
				".bkint($msg->uid).",
				'".bkstr($msg->tl)."',
				'".bkstr($pubkey)."',
				".$contentid.",
				".bkint($msg->prt).",
				".ForumTopic::ST_OPENED.",
				".TIMENOW.",
				".TIMENOW.",
				'".bkstr(Abricos::$LNG)."'
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function TopicUpdate(Ab_Database $db, $msg, $userid){
		$info = ForumQuery::Topic($db, $msg->id, $userid, true);
		Ab_CoreQuery::ContentUpdate($db, $info['ctid'], $msg->bd);
		$sql = "
			UPDATE ".$db->prefix."frm_topic
			SET
				title='".bkstr($msg->tl)."',
				upddate=".TIMENOW."
			WHERE topicid=".bkint($msg->id)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function TopicFields (Ab_Database $db){
		return "
			m.topicid as id,
			m.userid as uid,
			m.title as tl,
			m.isprivate as prt,
			m.dateline as dl,
			m.status as st,
			m.statuserid as stuid,
			m.statdate as stdl,
			m.upddate as udl,
			m.cmtcount as cmt,
			m.cmtdate as cmtdl,
			m.cmtuserid as cmtuid 
		";
	}
	
	public static function TopicList(Ab_Database $db, ForumTopicListConfig $cfg){
		
		$lastupdate = bkint($cfg->lastUpdate);
		$limit = $cfg->limit;

		$sql = "
			SELECT ".ForumQuery::TopicFields($db)."
		";
		if ($cfg->withDetail){
			$sql .= ",
				c.body as bd,
				c.contentid as ctid
			";
		}
		$sql .= "
			FROM ".$db->prefix."frm_topic m
		";
		if ($cfg->withDetail){
			$sql .= "
				INNER JOIN ".$db->prefix."content c ON m.contentid=c.contentid
			";
		}
		$sql .="
			WHERE (m.upddate > ".$lastupdate." OR m.cmtdate > ".$lastupdate.")
				AND language='".bkstr(Abricos::$LNG)."'
		";
		if (!ForumManager::$instance->IsModerRole()){
			// приватные темы доступны только авторам и модераторам
			$sql .= " 
				AND (m.isprivate=0 OR (m.isprivate=1 AND m.userid=".bkint(Abricos::$user->id).")) 
				AND m.status != ".ForumTopic::ST_REMOVED."
			";
		}
		
			if (is_array($cfg->topicIds) && count($cfg->topicIds)){
			$limit = 1;
			$aWh = array();
			for ($i=0; $i<count($cfg->topicIds); $i++){
				array_push($aWh, "m.topicid=".bkint($cfg->topicIds[$i]));
			}
			$sql .= " AND (".implode(" OR ", $aWh).") ";
		}
		
		if (is_array($cfg->contentIds) && count($cfg->contentIds)){
			$limit = 1;
			$aWh = array();
			for ($i=0; $i<count($cfg->contentIds); $i++){
				array_push($aWh, "m.contentid=".bkint($cfg->contentIds[$i]));
			}
			$sql .= " AND (".implode(" OR ", $aWh).") ";
		}
		
		$sql .="
			ORDER BY m.upddate DESC
			LIMIT ".bkint($limit)."
		";
		
		return $db->query_read($sql);
	}
	
	public static function UserList(Ab_Database $db, $uids){
		$aWh = array();
		array_push($aWh, "u.userid=0");
		foreach ($uids as $uid){
			array_push($aWh, "u.userid=".bkint($uid));
		}
		
		$sql = "
			SELECT
				DISTINCT
				u.userid as id,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm,
				u.avatar as avt
			FROM ".$db->prefix."user u
			WHERE ".implode(" OR ", $aWh)."
		";
		return $db->query_read($sql);
	}
	
	public static function TopicCommentInfoUpdate(Ab_Database $db, $topicid){
		
		$sql = "
			SELECT
				(
					SELECT count(*) as cmt
					FROM ".$db->prefix."cmt_comment c
					WHERE c.contentid=m.contentid
					GROUP BY c.contentid
				) as cmt,
				(
					SELECT c4.dateedit as cmtdl
					FROM ".$db->prefix."cmt_comment c4
					WHERE m.contentid=c4.contentid
					ORDER BY c4.dateedit DESC
					LIMIT 1
				) as cmtdl,
				(
					SELECT c5.userid as cmtuid
					FROM ".$db->prefix."cmt_comment c5
					WHERE m.contentid=c5.contentid
					ORDER BY c5.dateedit DESC
					LIMIT 1
				) as cmtuid	
			FROM ".$db->prefix."frm_topic m
			INNER JOIN ".$db->prefix."content c ON m.contentid=c.contentid
			WHERE m.topicid=".bkint($topicid)." 
			LIMIT 1
		";
		$row = $db->query_first($sql);
				
		$sql = "
			UPDATE ".$db->prefix."frm_topic
			SET
				cmtcount=".bkint($row['cmt']).",
				cmtuserid=".bkint($row['cmtuid']).",
				cmtdate=".$row['cmtdl']."
			WHERE topicid=".bkint($topicid)."
			LIMIT 1
		";
		$db->query_write($sql);
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
				FROM ".$db->prefix."frm_topic m 
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
				f.filename as nm,
				f.filesize as sz
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
			UPDATE ".$db->prefix."frm_topic
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
			UPDATE ".$db->prefix."frm_topic
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