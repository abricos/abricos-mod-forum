<?php 
/**
 * @package Abricos
 * @subpackage Forum
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class ForumModule extends Ab_Module {
	
	public function __construct(){
		$this->version = "0.1.5";
		$this->name = "forum";
		$this->takelink = "forum";
		$this->permission = new ForumPermission($this);
	}
	
	/**
	 * Получить менеджер
	 *
	 * @return ForumManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/manager.php';
			$this->_manager = new ForumManager($this);
		}
		return $this->_manager;
	}
	
	public function GetContentName(){
		$cname = 'index';
		$adress = $this->registry->adress;
		
		if ($adress->level >= 2 && $adress->dir[1] == 'upload'){
			$cname = $adress->dir[1];
		}
		return $cname;
	}
	
	public function RSS_GetItemList($inBosUI = false){
		$ret = array();
		
		$manager = $this->GetManager();
		
		$url = $this->registry->adress->host;
		$url .= $inBosUI ? "/bos/" : "/forum/";
		$url .= "#app=forum/msgview/showMessageViewPanel/";
		
		$rows = $manager->MessageList(0, true);
		while (($row = $this->registry->db->fetch_array($rows))){
			
			$title = $row['tl'];
			$link = $url.$row['id']."/";
				
			$item = new RSSItem($title, $link, "", $row['dl']);
			$item->modTitle = $this->lang['modtitle'];
			array_push($ret, $item);
		}
		return $ret;
	}
	
}


class ForumAction {
	const VIEW	= 10;
	const WRITE	= 30;
	const MODER	= 40;
	const ADMIN	= 50;
}

class ForumGroup {
	
	/**
	 * Группа "Модераторы"
	 * @var string
	 */
	const MODERATOR = 'forum_moderator';
}


/**
 * Статус задачи
 */
class ForumStatus {
	
	/**
	 * Открыто
	 * @var integer
	 */
	const OPENED = 0;

	/**
	 * Закрыто
	 * @var integer
	 */
	const CLOSED = 1;
	
	/**
	 * Удалено
	 * @var integer
	 */
	const REMOVED = 2;
}


class ForumPermission extends Ab_UserPermission {
	
	public function ForumPermission(ForumModule $module){
		
		$defRoles = array(
			new Ab_UserRole(ForumAction::VIEW, Ab_UserGroup::GUEST),
			new Ab_UserRole(ForumAction::VIEW, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(ForumAction::VIEW, Ab_UserGroup::ADMIN),
			
			new Ab_UserRole(ForumAction::WRITE, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(ForumAction::WRITE, Ab_UserGroup::ADMIN),
			
			new Ab_UserRole(ForumAction::MODER, ForumGroup::MODERATOR),
			new Ab_UserRole(ForumAction::MODER, Ab_UserGroup::ADMIN),
			
			new Ab_UserRole(ForumAction::ADMIN, Ab_UserGroup::ADMIN)
		);
		parent::__construct($module, $defRoles);
	}
	
	public function GetRoles(){
		return array(
			ForumAction::VIEW => $this->CheckAction(ForumAction::VIEW),
			ForumAction::WRITE => $this->CheckAction(ForumAction::WRITE),
			ForumAction::MODER => $this->CheckAction(ForumAction::MODER),
			ForumAction::ADMIN => $this->CheckAction(ForumAction::ADMIN)
		);
	}
}

Abricos::ModuleRegister(new ForumModule());

?>