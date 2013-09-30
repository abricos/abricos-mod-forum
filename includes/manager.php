<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classes.php';
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
			case 'forumsave':		return $this->ForumSave($d->forum);
			case 'topicsave':		return $this->TopicSave($d->topic);
			case 'topic': 		return $this->Topic($d->topicid);
			case 'sync':			return $this->Sync();
			case 'topicclose': 	return $this->TopicClose($d->topicid);
			case 'topicremove': 	return $this->TopicRemove($d->topicid);
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
	
	public function ToArrayId($rows, $field = "id"){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row[$field]] = $row;
		}
		return $ret;
	}
		
	public function ToArray($rows, &$ids1 = "", $fnids1 = 'uid', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
			if (is_array($ids1)){ $ids1[$row[$fnids1]] = $row[$fnids1]; }
			if (is_array($ids2)){ $ids2[$row[$fnids2]] = $row[$fnids2]; }
			if (is_array($ids3)){ $ids3[$row[$fnids3]] = $row[$fnids3]; }
		}
		return $ret;
	}
		
	public function ParamToObject($o){
		if (is_array($o)){
			$ret = new stdClass();
			foreach($o as $key => $value){
				$ret->$key = $value;
			}
			return $ret;
		}else if (!is_object($o)){
			return new stdClass();
		}
		return $o;
	}
	
	public function Sync(){ return TIMENOW; }
	
	public function Bos_OnlineData(){
		if (!$this->IsViewRole()){ return null; }
		exit;
		// $rows = ForumQuery::TopicList($this->db, $this->userid, $this->IsModerRole(), 0, 15);
		// return $this->ToArray($rows);
	}
	
	public function BoardData($lastupdate = 0, $orderByDateLine = false){
		if (!$this->IsViewRole()){ return null; }
		$ret = $this->TopicListToAJAX($lastupdate, $orderByDateLine);
		
		$uids = array();
		
		$rows = ForumQuery::TopicList($this->db, $this->userid, $this->IsModerRole(), $lastupdate, 15, $orderByDateLine);
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
		
		// $ret->users = $this->ToArray(ForumQuery::Users($this->db, $uids));

		return $ret;
	}
	
	public function ForumSave($sd){
		if (!$this->IsAdminRole()){ return null; }
	
		$sd->id = intval($sd->id);

		$parserFull = Abricos::TextParser(true);
		$utmanager = Abricos::TextParser();
		$sd->tl = $parserFull->Parser($sd->tl);
		$sd->bd = $utmanager->Parser($sd->bd);
	
		if ($sd->id == 0){
			$sd->uid = $this->userid;
			$sd->id = ForumQuery::ForumAppend($this->db, $sd);
		}else{
			ForumQuery::ForumUpdate($this->db, $sd);
		}
	
		return $this->ForumList();
	}
	
	public function ForumList(){
		if (!$this->IsViewRole()){ return null; }
	
		$rows = ForumQuery::ForumList($this->db);
		return $this->ToArray($rows);
	}
	
	/**
	 * Получить список пользователей
	 * @param ForumTopic|array|integer $uids 
	 */
	public function UserList($uids){
		if (!$this->IsViewRole()){ return null; }
		
		if ($uids instanceof ForumTopic){
			$uids = array($uids->userid, $uids->lastUserId);
		}else if (!is_array($uids)){
			$userIds = array(intval($uids));
		}
		
		$list = new ForumUserList();
		$rows = ForumQuery::UserList($this->db, $uids);
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new ForumUser($d));
		}
		return $list;
	}

	private $_cacheTopicCID;
	private $_cacheTopic;
	
	private function _TopicInitCache($clearCache){
		if ($clearCache){
			$this->_cacheTopic = null;
			$this->_cacheTopicCID = null;
		}
			if (!is_array($this->_cacheTopic)){
			$this->_cacheTopic = array();
		}
		if (!is_array($this->_cacheTopicCID)){
			$this->_cacheTopicCID = array();
		}
	}
	
	/**
	 * Тема форума
	 * 
	 * @param integer $topicid
	 * @param boolean $clearCache
	 * @return ForumTopic
	 */
	public function Topic($topicid, $clearCache = false){
		if (!$this->IsViewRole()){ return null; }
		
		$this->_TopicInitCache($clearCache);
		
		if (!empty($this->_cacheTopic[$topicid])){
			return $this->_cacheTopic[$topicid];
		}
		
		$cfg = new ForumTopicListConfig();
		$cfg->topicIds = array($topicid);
		$cfg->withDetail = true;
		
		$list = $this->TopicList($cfg);
		$topic = $this->_cacheTopic[$topicid] = $list->GetByIndex(0);
		if (empty($topic)){ return null; }
		
		$this->_cacheTopicCID[$topic->detail->contentid] = $topic;
		
		return $topic;
	}
	
	/**
	 * Тема форума по глобальному идентификатору контента
	 * @param integer $contentid
	 * @param boolean $clearCache
	 */
	public function TopicByContentId($contentid, $clearCache = false){
		if (!$this->IsViewRole()){ return null; }
		
		$this->_TopicInitCache($clearCache);
		
		if (!empty($this->_cacheTopicCID[$contentid])){
			return $this->_cacheTopicCID[$contentid];
		}
		
		$cfg = new ForumTopicListConfig();
		$cfg->contentIds = array($contentid);
		$cfg->withDetail = true;
		
		$list = $this->TopicList($cfg);
		$topic = $list->GetByIndex(0);
		if (empty($topic)){ return null; }
		
		$this->_cacheTopic[$topic->id] = 
			$this->_cacheTopicCID[$contentid] = $topic;
		
		return $topic;
	}
	
	/**
	 * Список сообщений
	 * 
	 * @param ForumTopicListConfig|array|object $cfg
	 * @return ForumTopicList
	 */
	public function TopicList($cfg = null){
		if (!$this->IsViewRole()){ return null; }
		
		if ($cfg instanceof ForumTopicListConfig){
		}else{
			$cfg = new ForumTopicListConfig($this->ParamToObject($cfg));
		}
		
		$list = new ForumTopicList();
		
		$rows = ForumQuery::TopicList($this->db, $cfg);
		while (($d = $this->db->fetch_array($rows))){
			$topic = new ForumTopic($d);
			if ($cfg->withDetail){
				$topic->detail = new ForumTopicDetail($d);
			}
				
			$list->Add($topic);
		}
		
		if (!$cfg->withDetail || $list->Count() == 0){
			return $list;
		}
		
		$rows = ForumQuery::TopicFileList($this->db, $list->Ids());
		while (($d = $this->db->fetch_array($rows))){
			$topic = $list->Get($d['tid']);
			$topic->detail->fileList->Add(new ForumFile($d));
		}
		return $list;
	}
	
	public function TopicListToAJAX($lastupdate = 0, $orderByDateLine = false){
		$list = $this->TopicList($lastupdate, $orderByDateLine);
		
		if (empty($list)){ return null; }

		if ($lastupdate == 0 || ($lastupdate > 0 && count($list->userIds) > 0)){
			$list->AddUserId(Abricos::$user->id);
		}
		$userList = $this->UserList($list->userIds);
		
		$ret = new stdClass();
		$ret->topics = $list->ToAJAX();
		$ret->hlid = $list->hlid;
		$ret->users = $userList->ToAJAX();
		
		return $ret;
	}
	
	/**
	 * Сохранить сообщение
	 * 
	 * @param object $sd
	 */
	public function TopicSave($sd){
		
		if (!$this->IsWriteRole()){ return null; }
		
		$sd->id = intval($sd->id);

		$parserFull = Abricos::TextParser(true);
		$utmanager = Abricos::TextParser();
		$sd->tl = $parserFull->Parser($sd->tl);
		$sd->bd = $utmanager->Parser($sd->bd);
		
		$sendNewNotify = false;
		
		if ($sd->id == 0){
			$sd->uid = $this->userid;
			$pubkey = md5(time().$this->userid);
			$sd->id = ForumQuery::TopicAppend($this->db, $sd, $pubkey);
			
			$sendNewNotify = true;
		}else{
			$topic = $this->Topic($sd->id);
			if (empty($topic) || !$topic->IsWrite()){ return null; }
			
			ForumQuery::TopicUpdate($this->db, $sd, $this->userid);
		}
		
		// обновить информацию по файлам
		$files = $this->TopicFiles($sd->id, true);
		$arr = $sd->files;

		foreach ($files as $rFileId => $cfile){
			$find = false;
			foreach ($arr as $file){
				if ($file->id == $rFileId){
					$find = true;
					break;
				}
			}
			if (!$find){
				ForumQuery::TopicFileRemove($this->db, $sd->id, $rFileId);
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
				ForumQuery::TopicFileAppend($this->db, $sd->id, $file->id, $this->userid);
			}
		}
		
		$topicid = $sd->id;
		
		$topic = $this->Topic($topicid, true);
		
		if ($sendNewNotify){
			// Отправить уведомление всем модераторам
			
			$brick = Brick::$builder->LoadBrickS('forum', 'templates', null, null);
			$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
			$plnk = "http://".$host."/bos/#app=forum/topicview/showTopicViewPanel/".$topic->id."/";
			
			$rows = ForumQuery::ModeratorList($this->db);
			while (($user = $this->db->fetch_array($rows))){
				if ($user['id'] == $this->userid){ continue; }
				
				$email = $user['eml'];
				if (empty($email)){ continue; }
				
				$subject = Brick::ReplaceVarByData($brick->param->var['newprojectsubject'], array(
					"tl" => $topic->title
				));
				$body = Brick::ReplaceVarByData($brick->param->var['newprojectbody'], array(
					"tl" => $topic->title,
					"plnk" => $plnk,
					"unm" => $this->UserNameBuild($this->user->info),
					"prj" => $topic->detail->body,
					"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
				));
				Abricos::Notify()->SendMail($email, $subject, $body);
			}
		}
		
		return $topic;
	}
	
	////////////////////////////// комментарии /////////////////////////////
	/**
	 * Есть ли разрешение на вывод списка комментариев
	 *  
	 * Метод запрашивает модуль Comment
	 * 
	 * @param integer $contentid
	 */
	public function Comment_IsViewList($contentid){
		if (!$this->IsViewRole()){ return null; }
	
		$topic = $this->TopicByContentId($contentid);
		return !empty($topic);
	}
	
	/**
	 * Есть ли разрешение на добавление комментария к топику?
	 *
	 * Метод запрашивает модуль Comment
	 *
	 * @param integer $contentid
	 */
	public function Comment_IsWrite($contentid){
		$topic = $this->TopicByContentId($contentid);
		if (empty($topic)){ return false; }
		
		return $topic->IsCommentWrite();
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
	public function Comment_SendNotify($data){
		if (!$this->IsViewRole()){ return; }

		// данные по комментарию:
		// $data->id	- идентификатор комментария
		// $data->pid	- идентификатор родительского комментария
		// $data->uid	- пользователь оставивший комментарий
		// $data->bd	- текст комментария
		// $data->cid	- идентификатор контента

		$topic = $this->TopicByContentId($data->cid);
		
		if (empty($topic) || !$topic->IsCommentWrite()){ return; }
		
		// комментарий добавлен, необходимо обновить инфу
		ForumQuery::TopicCommentInfoUpdate($this->db, $topic->id);
		
		
		$brick = Brick::$builder->LoadBrickS('forum', 'templates', null, null);
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
		$plnk = "http://".$host."/bos/#app=forum/topicview/showTopicViewPanel/".$topic->id."/";


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
						"tl" => $topic->title
					));
					$body = Brick::ReplaceVarByData($brick->param->var['cmtemlansbody'], array(
						"tl" => $topic->title,
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
		if ($topic->userid != $this->userid){
			$autor = UserQuery::User($this->db, $topic->userid);
			$email = $autor['email'];
			if (!empty($email) && !$emails[$email]){
				$emails[$email] = true;
				$subject = Brick::ReplaceVarByData($brick->param->var['cmtemlautorsubject'], array(
					"tl" => $topic->title
				));
				$body = Brick::ReplaceVarByData($brick->param->var['cmtemlautorbody'], array(
					"tl" => $topic->title,
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
				"tl" => $topic->title
			));
			$body = Brick::ReplaceVarByData($brick->param->var['cmtemlbody'], array(
				"tl" => $topic->title,
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
	 * @param integer $topicid
	 */
	public function TopicClose($topicid){
		if (!$this->IsModerRole()){ return null; }
		
		$topic = $this->Topic($topicid);
		if (empty($topic) || !$topic->IsWrite()){ return null; }
		
		ForumQuery::TopicSetStatus($this->db, $topicid, ForumTopic::ST_CLOSED, $this->userid);
		
		return $this->Topic($topicid);
	}
	
	/**
	 * Удалить сообщение. Роль модератора
	 * 
	 * @param integer $topicid
	 */
	public function TopicRemove($topicid){
		if (!$this->IsModerRole()){ return null; }
		
		$topic = $this->Topic($topicid);
		if (empty($topic) || !$topic->IsWrite()){ return null; }
				
		ForumQuery::TopicSetStatus($this->db, $topicid, ForumTopic::ST_REMOVED, $this->userid);
		
		return $this->Topic($topicid);
	}

}

?>