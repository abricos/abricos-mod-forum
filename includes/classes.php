<?php 
/**
 * @package Abricos
 * @subpackage Forum
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class Forum extends AbricosItem {
	
	public $title;
	
	public function __construct($d){
		parent::__construct($d);
		$this->title = strval($d['tl']);
	}
	
}

class ForumList extends AbricosList {
	
	/**
	 * @return Forum
	 */
	public function Get($id){
		return parent::Get($id);
	}
	
	/**
	 * @return Forum
	 */
	public function GetByIndex($i){
		return parent::GetByIndex($i);
	}
}

class ForumTopic extends AbricosItem {
	
	const ST_OPENED = 0;
	const ST_CLOSED = 1;
	const ST_REMOVED = 2;
	
	public $userid;
	public $title;
	public $dateline;
	public $upddate;
	public $commentCount;
	public $lastCommentDate;
	public $lastUserId;
	public $status;
	
	private $_isPrivate;
	
	/**
	 * @var ForumTopicDetail
	 */
	public $detail = null;
	
	public function __construct($d){
		parent::__construct($d);
		
		$this->userid = intval($d['uid']);
		$this->title = strval($d['tl']); 
		$this->dateline = intval($d['dl']);
		$this->upddate = intval($d['upd']);
		$this->commentCount = intval($d['cmt']);
		$this->lastCommentDate = intval($d['cmtdl']);
		$this->lastUserId = intval($d['cmtuid']);
		$this->status = intval($d['st']);
		
		$this->_isPrivate = intval($d['prt']) > 0;
	}
	
	public function IsPrivate(){
		return $this->_isPrivate;
	}
	
	public function IsClosed(){
		return $this->status == ForumTopic::ST_CLOSED;
	}
	
	public function IsRemoved(){
		return $this->status == ForumTopic::ST_REMOVED;
	}
	
	public function URI(){
		return "/forum/topic_".$this->id."/";
	}
}

class ForumTopicDetail {
	public $body;
	public $contentid;
	
	public function __construct($d){
		
	}
}

class ForumTopicList extends AbricosList {
	
	/**
	 * Идентификаторы пользователей
	 * @var array
	 */
	public $userIds = array();
	private $_checkUsers;

	/**
	 * Дата последнего обновления
	 * @var integer
	 */
	public $hlid;
	
	public function __construct(){
		parent::__construct();
		
		if (func_num_args()>0){
			$this->hlid = intval(func_get_arg(0));
		}else{
			$this->hlid = 0;
		}
	}
	
	/**
	 * @return ForumTopic
	 */
	public function Get($id){
		return parent::Get($id);
	}
	
	/**
	 * @return ForumTopic
	 */
	public function GetByIndex($i){
		return parent::GetByIndex($i);
	}
	
	public function AddUserId($userid){
		if ($this->_checkUsers[$userid]){
			return;
		}
		$this->_checkUsers[$userid] = true;
		array_push($this->userIds, $userid);
	}
	
	/**
	 * Добавить сообщение в список
	 * @param ForumTopic $item
	 */
	public function Add($item){
		parent::Add($item);
		
		$this->AddUserId($item->userid);
		$this->AddUserId($item->lastUserId);
		
		$this->hlid = max(max($this->hlid, $item->upddate), $item->lastCommentDate);
	}
}

/**
 * Настройки списка сообщений форума
 */
class ForumTopicListConfig {

	/**
	 * Лимит записей
	 * @var integer
	 */
	public $limit = 50;
	
	/**
	 * Все записи с последней даты обновления
	 * @var integer
	 */
	public $lastUpdate = 0;
	
	/**
	 * Сортировать список по дате создания
	 * @var boolean
	 */
	public $orderByDateLine = false;
	
	/**
	 * Идентификаторы тем
	 * @var array
	 */
	public $topicIds;
	
	/**
	 * Глобальные идентификаторы контента 
	 * @var array
	 */
	public $contentIds;
	
	public $withDetail = false;
	
	public function __construct(){
		
	}
}

class ForumUser extends AbricosItem {
	public $userName;
	public $avatar;
	public $firstName;
	public $lastName;

	/**
	 * Почта пользователя
	 *
	 * Для внутреннего использования
	 * @var string
	 */
	public $email;

	public function __construct($d){
		$this->id			= intval($d['uid'])>0 ? $d['uid'] : $d['id'];
		$this->userName		= strval($d['unm']);
		$this->avatar		= strval($d['avt']);
		$this->firstName	= strval($d['fnm']);
		$this->lastName		= strval($d['lnm']);
		$this->email		= strval($d['eml']);
	}

	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id = $this->id;
		$ret->unm = $this->userName;
		$ret->avt = $this->avatar;
		$ret->fnm = $this->firstName;
		$ret->lnm = $this->lastName;
		return $ret;
	}

	public function GetUserName(){
		if (!empty($this->firstName) && !empty($this->lastName)){
			return $this->firstName." ".$this->lastName;
		}
		return $this->userName;
	}

	public function URL(){
		$mod = Abricos::GetModule('uprofile');
		if (empty($mod)){
			return "#";
		}
		return '/uprofile/'.$this->id.'/';
	}

	private function Avatar($size){
		$url = empty($this->avatar) ?
		'/modules/uprofile/images/nofoto'.$size.'.gif' :
		'/filemanager/i/'.$this->avatar.'/w_'.$size.'-h_'.$size.'/avatar.gif';
		return '<img src="'.$url.'">';
	}

	public function Avatar24(){
		return $this->Avatar(24);
	}

	public function Avatar90(){
		return $this->Avatar(90);
	}
}

class ForumUserList extends AbricosList {

	/**
	 * @return ForumUser
	 */
	public function Get($id){
		return parent::Get($id);
	}

	/**
	 * @return ForumUser
	 */
	public function GetByIndex($i){
		return parent::GetByIndex($i);
	}
}

?>