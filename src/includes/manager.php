<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class ForumManager
 *
 * @property ForumModule $module
 */
class ForumManager extends Ab_ModuleManager {

    public function IsAdminRole(){
        return $this->IsRoleEnable(ForumAction::ADMIN);
    }

    public function IsModerRole(){
        if ($this->IsAdminRole()){
            return true;
        }
        return $this->IsRoleEnable(ForumAction::MODER);
    }

    public function IsWriteRole(){
        if ($this->IsModerRole()){
            return true;
        }
        return $this->IsRoleEnable(ForumAction::WRITE);
    }

    public function IsViewRole(){
        if ($this->IsWriteRole()){
            return true;
        }
        return $this->IsRoleEnable(ForumAction::VIEW);
    }

    private $_app = null;

    /**
     * @return ForumApp
     */
    public function GetApp(){
        if (!is_null($this->_app)){
            return $this->_app;
        }
        $this->module->ScriptRequire('includes/app.php');
        return $this->_app = new ForumApp($this);
    }

    public function AJAX($d){
        return $this->GetApp()->AJAX($d);
    }

    public function Bos_MenuData(){
        $i18n = $this->module->I18n();
        return array(
            array(
                "name" => "forum",
                "title" => $i18n->Translate('title'),
                "role" => ForumAction::VIEW,
                "icon" => "/modules/forum/images/forum-24.png",
                "url" => "forum/wspace/ws"
            )
        );
    }

}

?>