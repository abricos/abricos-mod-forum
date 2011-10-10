<?php 
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Forum
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

class ForumModule extends CMSModule {
	
	public function __construct(){
		$this->version = "0.1";
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
		$cname = '';
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


class ForumPermission extends AbricosPermission {
	
	public function ForumPermission(ForumModule $module){
		
		$defRoles = array(
			new AbricosRole(ForumAction::VIEW, UserGroup::GUEST),
			new AbricosRole(ForumAction::VIEW, UserGroup::REGISTERED),
			new AbricosRole(ForumAction::VIEW, UserGroup::ADMIN),
			
			new AbricosRole(ForumAction::WRITE, UserGroup::REGISTERED),
			new AbricosRole(ForumAction::WRITE, UserGroup::ADMIN),
			
			new AbricosRole(ForumAction::MODER, ForumGroup::MODERATOR),
			new AbricosRole(ForumAction::MODER, UserGroup::ADMIN),
			
			new AbricosRole(ForumAction::ADMIN, UserGroup::ADMIN)
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

$mod = new ForumModule();
CMSRegistry::$instance->modules->Register($mod);

?>