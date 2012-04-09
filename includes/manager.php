<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Forum
 * @copyright Copyright (C) 2011 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

require_once 'dbquery.php';

class ForumManager extends Ab_ModuleManager {
	
	/**
	 * @var ForumModule
	 */
	public $module = null;
	
	/**
	 * @var ForumManager
	 */
	public static $instance = null; 
	
	public function __construct(ForumModule $module){
		parent::__construct($module);
		ForumManager::$instance = $this;
	}
	
	public function IsAdminRole(){
		return $this->IsRoleEnable(ForumAction::ADMIN);
	}
	
	public function IsModerRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->IsRoleEnable(ForumAction::MODER);
	}
	
	public function IsWriteRole(){
		if ($this->IsModerRole()){ return true; }
		return $this->IsRoleEnable(ForumAction::WRITE);
	}
	
	public function IsViewRole(){
		if ($this->IsWriteRole()){ return true; }
		return $this->IsRoleEnable(ForumAction::VIEW);
	}
	
	private function _AJAX($d){
		
		switch($d->do){
			case 'messagesave':		return $this->MessageSave($d->message);
			case 'message': 		return $this->Message($d->messageid);
			case 'sync':			return $this->Sync();
			case 'messageclose': 	return $this->MessageClose($d->messageid);
			case 'messageremove': 	return $this->MessageRemove($d->messageid);
		}
		return null;
	}
	
	public function AJAX($d){
		if ($d->do == "init"){
			return $this->BoardData(0);
		}
		$ret = new stdClass();
		$ret->u = $this->userid;
		$ret->r = $this->_AJAX($d);
		$ret->changes = $this->BoardData($d->hlid);
		
		return $ret;
	}
	
	private function ToArrayById($rows){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row['id']] = $row;
		}
		return $ret;
	}
	
	private function ToArray($rows){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
		}
		return $ret;
	}
	
	public function Sync(){ return TIMENOW; }
	
	public function Bos_OnlineData(){
		if (!$this->IsViewRole()){ return null; }
		
		$rows = ForumQuery::MessageList($this->db, $this->userid, $this->IsModerRole(), 0, 15);
		return $this->ToArray($rows);
	}
	
	public function BoardData($lastupdate = 0, $orderByDateLine = false){
		if (!$this->IsViewRole()){ return null; }
		$ret = new stdClass();
		$ret->board = array();
		$ret->hlid = $lastupdate;
		
		$uids = array();
		
		$rows = ForumQuery::MessageList($this->db, $this->userid, $this->IsModerRole(), $lastupdate, 15, $orderByDateLine);
		while (($row = $this->db->fetch_array($rows))){
			$ret->hlid = max($ret->hlid, intval($row['udl']));
			
			// время последнего комментария тоже участвует в определении изменений
			$ret->hlid = max($ret->hlid, intval($row['cmtdl']));
			
			$uids[$row['uid']] = true;
			$uids[$row['cmtuid']] = true;
			
			$ret->board[$row['id']] = $row;
		}
		if ($lastupdate == 0 || ($lastupdate > 0 && count($uids) > 0)){
			$uids[$this->userid] = true;
		}
		$ret->users = array();
		if (count($uids) > 0){
			$rows = ForumQuery::Users($this->db, $uids);
			$ret->users = $this->ToArrayById($rows);
		}
		
		return $ret;
	}
	
	public function MessageList($lastupdate = 0, $orderByDateLine = false){
		if (!$this->IsViewRole()){ return null; }
		
		$rows = ForumQuery::MessageList($this->db, $this->userid, $this->IsModerRole(), $lastupdate, 15, $orderByDateLine);
		return $rows;
	}
	
	/**
	 * Сохранить сообщение
	 * 
	 * @param object $msg
	 */
	public function MessageSave($msg){
		
		if (!$this->IsWriteRole()){ return null; }
		
		$msg->id = intval($msg->id);
		
		$utmanager = Abricos::TextParser();
		$msg->tl = $utmanager->Parser($msg->tl);
		if (!$this->IsAdminRole()){
			// порезать теги у описания
			$msg->bd = $utmanager->Parser($msg->bd);
		}
		
		$sendNewNotify = false;
		
		if ($msg->id == 0){
			$msg->uid = $this->userid;
			$pubkey = md5(time().$this->userid);
			$msg->id = ForumQuery::MessageAppend($this->db, $msg, $pubkey);
			
			$sendNewNotify = true;
		}else{
			$info = $this->Message($msg->id);
			if (!$this->MessageAccess($info)){
				return null;
			}
			
			if ($info['st'] == ForumStatus::CLOSED ||
				$info['st'] == ForumStatus::REMOVED ){ 
				return null; 
			}
			
			ForumQuery::MessageUpdate($this->db, $msg, $this->userid);
		}
		
		// обновить информацию по файлам
		$files = $this->MessageFiles($msg->id, true);
		$arr = $msg->files;

		foreach ($files as $rFileId => $cfile){
			$find = false;
			foreach ($arr as $file){
				if ($file->id == $rFileId){
					$find = true;
					break;
				}
			}
			if (!$find){
				ForumQuery::MessageFileRemove($this->db, $msg->id, $rFileId);
			}
		}
		foreach ($arr as $file){
			$find = false;
			foreach ($files as $rFileId => $cfile){
				if ($file->id == $rFileId){
					$find = true;
					break;
				}
			}
			if (!$find){
				ForumQuery::MessageFileAppend($this->db, $msg->id, $file->id, $this->userid);
			}
		}
		
		$messageid = $msg->id;
		
		$message = $this->Message($messageid);
		
		if ($sendNewNotify){
			// Отправить уведомление всем модераторам
			
			$brick = Brick::$builder->LoadBrickS('forum', 'templates', null, null);
			$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
			$plnk = "http://".$host."/bos/#app=forum/msgview/showMessageViewPanel/".$message['id']."/";
			
			$rows = ForumQuery::ModeratorList($this->db);
			while (($user = $this->db->fetch_array($rows))){
				if ($user['id'] == $this->userid){ continue; }
				
				$email = $user['eml'];
				if (empty($email)){ continue; }
				
				$subject = Brick::ReplaceVarByData($brick->param->var['newprojectsubject'], array(
					"tl" => $message['tl']
				));
				$body = Brick::ReplaceVarByData($brick->param->var['newprojectbody'], array(
					"tl" => $message['tl'],
					"plnk" => $plnk,
					"unm" => $this->UserNameBuild($this->user->info),
					"prj" => $message['bd'],
					"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
				));
				Abricos::Notify()->SendMail($email, $subject, $body);
			}
		}
		
		return $message;
	}
	
	/**
	 * Если текущий пользователь модератор и выше или этот пользователь автор сообщения, то вернет истину
	 * @param array $msg
	 */
	public function MessageAccess($msg){
		if (!$this->IsViewRole() || empty($msg)){ return false; }
		if ($this->IsModerRole()){ return true; }
		
		if ($msg['prt'] == 1 && $this->userid != $msg['uid']){ return false; }
		
		return true;
	}
	
	public function Message($messageid){
		$msg = ForumQuery::Message($this->db, $messageid, true);
		if (!$this->MessageAccess($msg)){ return null; }
		
		$msg['files'] = array();
		$files = $this->MessageFiles($messageid, true);
		foreach ($files as $file){
			array_push($msg['files'], $file);
		}
		return $msg;
	}
		
	public function MessageFiles($messageid, $retarray = false){
		if (!$this->IsViewRole()){ return null; }
		$rows = ForumQuery::MessageFiles($this->db, $messageid);
		if (!$retarray){ return $rows; }
		return $this->ToArrayById($rows);
	}
	
	////////////////////////////// комментарии /////////////////////////////
	public function CommentList(){
		if (!$this->IsViewRole()){ return null; }
		
		$rows = ForumQuery::CommentList($this->db, $this->userid);
		return $this->ToArrayById($rows);
	}
	
	public function IsCommentList($contentid){
		if (!$this->IsViewRole()){ return null; }
		$message = ForumQuery::MessageByContentId($this->db, $contentid, true);
		return $this->MessageAccess($message);
	}
	
	public function IsCommentAppend($contentid){
		$message = ForumQuery::MessageByContentId($this->db, $contentid, true);
		if (!$this->MessageAccess($message)){ return false; }
		if ($message['st'] == ForumStatus::CLOSED || $message['st'] == ForumStatus::REMOVED){ return false; }
		
		return true;
	}
	
	private function UserNameBuild($user){
		$firstname = !empty($user['fnm']) ? $user['fnm'] : $user['firstname']; 
		$lastname = !empty($user['lnm']) ? $user['lnm'] : $user['lastname']; 
		$username = !empty($user['unm']) ? $user['unm'] : $user['username'];
		return (!empty($firstname) && !empty($lastname)) ? $firstname." ".$lastname : $username;
	}
	
	/**
	 * Отправить уведомление о новом комментарии.
	 * 
	 * @param object $data
	 */
	public function CommentSendNotify($data){
		if (!$this->IsViewRole()){ return; }
		
		// данные по комментарию:
		// $data->id	- идентификатор комментария
		// $data->pid	- идентификатор родительского комментария
		// $data->uid	- пользователь оставивший комментарий
		// $data->bd	- текст комментария
		// $data->cid	- идентификатор контента

		$message = ForumQuery::MessageByContentId($this->db, $data->cid, true);
		if (!$this->MessageAccess($message)){ return; }
		
		// комментарий добавлен, необходимо обновить инфу
		ForumQuery::MessageCommentInfoUpdate($this->db, $message['id']);
		
		
		$brick = Brick::$builder->LoadBrickS('forum', 'templates', null, null);
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
		$plnk = "http://".$host."/bos/#app=forum/msgview/showMessageViewPanel/".$message['id']."/";


		$emails = array();
		
		// уведомление "комментарий на комментарий"
		if ($data->pid > 0){
			$parent = CommentQuery::Comment($this->db, $data->pid, $data->cid, true);
			if (!empty($parent) && $parent['uid'] != $this->userid){
				$user = UserQuery::User($this->db, $parent['uid']);
				$email = $user['email'];
				if (!empty($email)){
					$emails[$email] = true;
					$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlanssubject'], array(
						"tl" => $message['tl']
					));
					$body = Brick::ReplaceVarByData($brick->param->var['cmtemlansbody'], array(
						"tl" => $message['tl'],
						"plnk" => $plnk,
						"unm" => $this->UserNameBuild($this->user->info),
						"cmt1" => $parent['bd']." ",
						"cmt2" => $data->bd." ",
						"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
					));
					Abricos::Notify()->SendMail($email, $subject, $body);
				}
			}
		}
		
		// уведомление автору
		if ($message['uid'] != $this->userid){
			$autor = UserQuery::User($this->db, $message['uid']);
			$email = $autor['email'];
			if (!empty($email) && !$emails[$email]){
				$emails[$email] = true;
				$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlautorsubject'], array(
					"tl" => $message['tl']
				));
				$body = Brick::ReplaceVarByData($brick->param->var['cmtemlautorbody'], array(
					"tl" => $message['tl'],
					"plnk" => $plnk,
					"unm" => $this->UserNameBuild($this->user->info),
					"cmt" => $data->bd." ",
					"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
				));
				Abricos::Notify()->SendMail($email, $subject, $body);
			}
		}
				
		// уведомление модераторам
		$rows = ForumQuery::ModeratorList($this->db);
		while (($user = $this->db->fetch_array($rows))){
			$email = $user['eml'];
			
			if (empty($email) || $emails[$email] || $user['id'] == $this->userid){
				continue;
			}
			$emails[$email] = true;
			$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlsubject'], array(
				"tl" => $message['tl']
			));
			$body = Brick::ReplaceVarByData($brick->param->var['cmtemlbody'], array(
				"tl" => $message['tl'],
				"plnk" => $plnk,
				"unm" => $this->UserNameBuild($this->user->info),
				"cmt" => $data->bd." ",
				"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
			));
			Abricos::Notify()->SendMail($email, $subject, $body);
		}
	}		
	
	/**
	 * Закрыть сообщение. Роль модератора
	 * 
	 * @param integer $messageid
	 */
	public function MessageClose($messageid){
		if (!$this->IsModerRole()){ return null; }
		
		$msg = $this->Message($messageid);
		if ($msg['st'] != ForumStatus::OPENED){ return null; } // закрыть можно только открытое сообщение
		
		ForumQuery::MessageSetStatus($this->db, $messageid, ForumStatus::CLOSED, $this->userid);
		
		return $this->Message($messageid);
	}
	
	/**
	 * Удалить сообщение. Роль модератора
	 * 
	 * @param integer $messageid
	 */
	public function MessageRemove($messageid){
		if (!$this->IsModerRole()){ return null; }
		
		$msg = $this->Message($messageid);
		if ($msg['st'] != ForumStatus::OPENED){ return null; } // закрыть можно только открытое сообщение
		
		ForumQuery::MessageSetStatus($this->db, $messageid, ForumStatus::REMOVED, $this->userid);
		
		return $this->Message($messageid);
	}

}

?>