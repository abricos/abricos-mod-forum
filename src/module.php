<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$commentModule = Abricos::GetModule('comment');
if (empty($commentModule)){
    return;
}

Abricos::GetModule('notify');

/**
 * Class ForumModule
 */
class ForumModule extends Ab_Module {

    /**
     * @var ForumModule
     */
    public static $instance;

    public function __construct(){
        ForumModule::$instance = $this;
        $this->version = "0.1.8";
        $this->name = "forum";
        $this->takelink = "forum";
        $this->permission = new ForumPermission($this);
    }

    private $_manager = null;

    /**
     * @return ForumManager
     */
    public function GetManager(){
        if (empty($this->_manager)){
            require_once 'includes/manager.php';
            $this->_manager = new ForumManager($this);
        }
        return $this->_manager;
    }

    /**
     * @var ForumTopic
     */
    public $currentTopic;

    public function GetContentName(){
        $cname = 'index';
        $adress = Abricos::$adress;

        if ($adress->level >= 2){
            $d2 = $adress->dir[1];
            if ($d2 == 'upload'){
                return 'upload';
            }

            $a = explode("_", $d2);

            if ($a[0] == 'topic'){
                return 'topic';
            }
        }

        return $cname;
    }

    public function RSS_GetItemList($inBosUI = false){
        $ret = array();

        $manager = $this->GetManager();

        $host = $this->registry->adress->host;

        $cfg = new ForumTopicListConfig();
        $cfg->limit = 30;
        $cfg->orderByDateLine = true;

        $i18n = $this->GetI18n();
        $mList = $manager->TopicList($cfg);
        for ($i = 0; $i < $mList->Count(); $i++){
            $msg = $mList->GetByIndex($i);

            $item = new RSSItem($msg->title, $msg->URI(), "", $msg->dateline);
            $item->modTitle = $i18n['title'];
            array_push($ret, $item);
        }
        return $ret;
    }

    /**
     * This module added menu item in BOS Panel
     *
     * @return bool
     */
    public function Bos_IsMenu(){
        return true;
    }
}


class ForumAction {
    const VIEW = 10;
    const WRITE = 30;
    const MODER = 40;
    const ADMIN = 50;
}

class ForumGroup {

    /**
     * Группа "Модераторы"
     *
     * @var string
     */
    const MODERATOR = 'forum_moderator';
}

class ForumPermission extends Ab_UserPermission {

    public function __construct(ForumModule $module){
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
