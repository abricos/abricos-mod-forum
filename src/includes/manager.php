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
        require_once 'dbquery.php';
        require_once 'classes/app.php';
        return $this->_app = new ForumApp($this);
    }

    public function AJAX($d){
        return $this->GetApp()->AJAX($d);
    }

    /*

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

    public function TopicByContentId($contentid, $clearCache = false){
        if (!$this->IsViewRole()){
            return null;
        }

        $contentid = intval($contentid);

        $this->_TopicInitCache($clearCache);

        if (!empty($this->_cacheTopicCID[$contentid])){
            return $this->_cacheTopicCID[$contentid];
        }

        $cfg = new ForumTopicListConfig();
        $cfg->contentIds = array($contentid);
        $cfg->withDetail = true;

        $list = $this->TopicList($cfg);
        $topic = $list->GetByIndex(0);
        if (empty($topic)){
            return null;
        }

        $this->_cacheTopic[$topic->id] = $this->_cacheTopicCID[$contentid] = $topic;

        return $topic;
    }

    public function TopicSave($sd){
        if (!$this->IsWriteRole()){
            return null;
        }

        $sd->id = isset($sd->id) ? intval($sd->id) : 0;

        $utmf = Abricos::TextParser(true);
        $utm = Abricos::TextParser();

        $sd->tl = $utmf->Parser($sd->title);
        $sd->bd = $utm->Parser($sd->body);

        $sd->prt = 0;

        $sendNewNotify = false;

        $topic = null;
        if ($sd->id == 0){
            $sd->uid = $this->userid;
        } else {
            $topic = $this->Topic($sd->id);
            if (empty($topic) || !$topic->IsWrite()){
                return null;
            }

            ForumQuery::TopicUpdate($this->db, $topic, $sd, $this->userid);
        }

        if (empty($topic)){
            return null;
        }


        $topic = $this->Topic($sd->id, true);


        return $topic->id;
    }

    public function TopicSaveToJSON($sd){
        $topicid = $this->TopicSave($sd);

        if (empty($topicid)){
            return null;
        }

        return $this->TopicToJSON($topicid);
    }


    public function TopicClose($topicid){
        if (!$this->IsModerRole()){
            return null;
        }

        $topic = $this->Topic($topicid);
        if (empty($topic) || !$topic->IsWrite()){
            return null;
        }

        ForumQuery::TopicSetStatus($this->db, $topicid, ForumTopic::ST_CLOSED, $this->userid);

        return $topicid;
    }

    public function TopicCloseToJSON($topicid){
        $topicid = $this->TopicClose($topicid);
        if (empty($topicid)){
            return null;
        }

        return $this->TopicToJSON($topicid);
    }

    public function TopicRemove($topicid){
        if (!$this->IsModerRole()){
            return null;
        }

        $topic = $this->Topic($topicid);
        if (empty($topic) || !$topic->IsWrite()){
            return null;
        }

        ForumQuery::TopicSetStatus($this->db, $topicid, ForumTopic::ST_REMOVED, $this->userid);

        return $topicid;
    }

    public function TopicRemoveToJSON($topicid){
        $topicid = $this->TopicRemove($topicid);
        if (empty($topicid)){
            return null;
        }

        return $this->TopicToJSON($topicid);
    }


    ////////////////////////////// комментарии /////////////////////////////


    public function Comment_IsViewList($contentid){
        if (!$this->IsViewRole()){
            return null;
        }

        $topic = $this->TopicByContentId($contentid);
        return !empty($topic);
    }


    public function Comment_IsWrite($contentid){
        $topic = $this->TopicByContentId($contentid);
        if (empty($topic)){
            return false;
        }

        return $topic->IsCommentWrite();
    }


    public function Comment_SendNotify($data){
        if (!$this->IsViewRole()){
            return;
        }

        // данные по комментарию:
        // $data->id	- идентификатор комментария
        // $data->pid	- идентификатор родительского комментария
        // $data->uid	- пользователь оставивший комментарий
        // $data->bd	- текст комментария
        // $data->cid	- идентификатор контента

        $topic = $this->TopicByContentId($data->cid);

        if (empty($topic) || !$topic->IsCommentWrite()){
            return;
        }

        // комментарий добавлен, необходимо обновить инфу
        ForumQuery::TopicCommentInfoUpdate($this->db, $topic->id);


        $brick = Brick::$builder->LoadBrickS('forum', 'templates', null, null);
        $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
        $plnk = "http://".$host.$topic->URI();

        $userManager = UserModule::$instance->GetManager();

        $emails = array();

        // уведомление "комментарий на комментарий"
        if ($data->pid > 0){
            $parent = CommentQuery::Comment($this->db, $data->pid, $data->cid, true);
            if (!empty($parent) && $parent['uid'] != $this->userid){

                $user = $userManager->User($parent['uid']);

                $email = $user->email;
                if (!empty($email)){
                    $emails[$email] = true;
                    $subject = Brick::ReplaceVarByData($brick->param->var['cmtemlanssubject'], array(
                        "tl" => $topic->title
                    ));
                    $body = Brick::ReplaceVarByData($brick->param->var['cmtemlansbody'], array(
                        "tl" => $topic->title,
                        "plnk" => $plnk,
                        "unm" => Abricos::$user->FullName(),
                        "cmt1" => $parent['bd']." ",
                        "cmt2" => $data->bd." ",
                        "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
                    ));
                    Abricos::Notify()->SendMail($email, $subject, $body);
                }
            }
        }

        // уведомление автору
        if ($topic->userid != $this->userid){
            $autor = $userManager->User($topic->userid);
            $email = $autor->email;
            if (!empty($email) && !isset($emails[$email])){
                $emails[$email] = true;
                $subject = Brick::ReplaceVarByData($brick->param->var['cmtemlautorsubject'], array(
                    "tl" => $topic->title
                ));
                $body = Brick::ReplaceVarByData($brick->param->var['cmtemlautorbody'], array(
                    "tl" => $topic->title,
                    "plnk" => $plnk,
                    "unm" => Abricos::$user->FullName(),
                    "cmt" => $data->bd." ",
                    "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
                ));
                Abricos::Notify()->SendMail($email, $subject, $body);
            }
        }

        // уведомление модераторам
        $rows = ForumQuery::ModeratorList($this->db);
        while (($userData = $this->db->fetch_array($rows))){
            $user = $userManager->User($userData['id']);
            $email = $user->email;

            if (empty($email) || isset($emails[$email]) || $user->id == $this->userid){
                continue;
            }
            $emails[$email] = true;
            $subject = Brick::ReplaceVarByData($brick->param->var['cmtemlsubject'], array(
                "tl" => $topic->title
            ));
            $body = Brick::ReplaceVarByData($brick->param->var['cmtemlbody'], array(
                "tl" => $topic->title,
                "plnk" => $plnk,
                "unm" => Abricos::$user->FullName(),
                "cmt" => $data->bd." ",
                "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
            ));
            Abricos::Notify()->SendMail($email, $subject, $body);
        }
    }
    /**/

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