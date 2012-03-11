<?php 
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Forum
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

class ForumModule extends Ab_Module {
	
	public function __construct(){
		$this->version = "0.1.1";
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