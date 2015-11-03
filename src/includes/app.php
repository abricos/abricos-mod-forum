<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'models.php';
require_once 'dbquery.php';

/**
 * Class ForumApp
 *
 * @property ForumManager $manager
 */
class ForumApp extends AbricosApplication {

    protected function GetClasses(){
        return array(
            'Topic' => 'ForumTopic',
            'TopicList' => 'ForumTopicList',
            'TopicStatus' => 'ForumTopicStatus',
            'TopicStatusList' => 'ForumTopicStatusList',
            'File' => 'ForumFile',
            'FileList' => 'ForumFileList',
        );
    }

    protected function GetStructures(){
        return 'Topic,TopicStatus,File';
    }

    private $_commentApp = null;

    /**
     * @return CommentApp
     */
    public function CommentApp(){
        if (!is_null($this->_commentApp)){
            return $this->_commentApp;
        }
        $module = Abricos::GetModule('comment');
        return $this->_commentApp = $module->GetManager()->GetApp();
    }

    private $_uprofileApp = null;

    /**
     * @return UProfileApp
     */
    public function UProfileApp(){
        if (!is_null($this->_uprofileApp)){
            return $this->_uprofileApp;
        }
        $module = Abricos::GetModule('uprofile');
        return $this->_uprofileApp = $module->GetManager()->GetApp();
    }

    private $_notifyApp = null;

    /**
     * @return NotifyApp
     */
    public function NotifyApp(){
        if (!is_null($this->_notifyApp)){
            return $this->_notifyApp;
        }
        $module = Abricos::GetModule('notify');
        return $this->_notifyApp = $module->GetManager()->GetApp();
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'topicList':
                return $this->TopicListToJSON($d->page);
            case 'topicListByIds':
                return $this->TopicListByIdsToJSON($d->topicids);
            case 'topic':
                return $this->TopicToJSON($d->topicid);
            case 'topicSave':
                return $this->TopicSaveToJSON($d->topic);
            case 'topicClose':
                return $this->TopicCloseToJSON($d->topicid);
            case 'topicOpen':
                return $this->TopicOpenToJSON($d->topicid);
            case 'topicRemove':
                return $this->TopicRemoveToJSON($d->topicid);
        }
        return null;
    }

    protected $_cache = array();

    public function CacheClear(){
        $this->_cache = array();
    }

    public function TopicSaveToJSON($d){
        $res = $this->TopicSave($d);
        $ret = $this->ResultToJSON('topicSave', $res);
        if (!is_integer($res)){
            $ret = $this->ImplodeJSON($this->TopicToJSON($res->topicid), $ret);
        }
        return $ret;
    }

    public function TopicSave($d){
        if (!$this->manager->IsWriteRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var ForumTopic $topic */
        $topic = $this->InstanceClass('Topic', $d);
        $sendNewNotify = false;

        if ($topic->id > 0){
            $curTopic = $this->Topic($topic->id);
            if (empty($curTopic)){
                return 404;
            }
        } else {
            /** @var ForumTopic $curTopic */
            $curTopic = $this->InstanceClass('Topic');
            $curTopic->pubkey = md5(time().Abricos::$user->id);
            $curTopic->userid = Abricos::$user->id;

            $sendNewNotify = true;
        }

        if (!$curTopic->IsWriteRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $curTopic->title = Abricos::TextParser(true)->Parser($topic->title);
        $curTopic->body = Abricos::TextParser()->Parser($topic->body);

        if ($topic->id === 0){
            $topic->id = ForumQuery::TopicAppend($this, $curTopic);
        } else {
            ForumQuery::TopicUpdate($this, $curTopic);
        }

        ForumQuery::TopicFilesRemove($this, $topic->id);

        if (isset($d->files) && is_array($d->files)){
            for ($i = 0; $i < count($d->files); $i++){
                ForumQuery::TopicFileAppend($this, $topic->id, $d->files[$i], $topic->userid);
            }
        }
        $this->CacheClear();
        $topic = $this->Topic($topic->id);

        if (empty($topic)){
            return 500;
        }

        if ($sendNewNotify){
            // Отправить уведомление всем модераторам

            $brick = Brick::$builder->LoadBrickS('forum', 'templates', null, null);
            $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
            $plnk = "http://".$host.$topic->URI();

            $rows = ForumQuery::ModeratorList($this);
            while (($userData = $this->db->fetch_array($rows))){
                if ($userData['id'] == Abricos::$user->id){
                    continue;
                }

                $email = $userData['eml'];
                if (empty($email)){
                    continue;
                }

                $subject = Brick::ReplaceVarByData($brick->param->var['newprojectsubject'], array(
                    "tl" => $topic->title
                ));
                $body = Brick::ReplaceVarByData($brick->param->var['newprojectbody'], array(
                    "tl" => $topic->title,
                    "plnk" => $plnk,
                    "unm" => Abricos::$user->FullName(),
                    "prj" => $topic->body,
                    "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
                ));
                Abricos::Notify()->SendMail($email, $subject, $body);
            }
        }

        $ret = new stdClass();
        $ret->topicid = $topic->id;
        return $ret;
    }

    public function TopicCloseToJSON($topicid){
        $res = $this->TopicClose($topicid);
        $ret = $this->ResultToJSON('TopicClose', $res);

        if (!is_integer($res)){
            $ret = $this->ImplodeJSON($this->TopicToJSON($topicid), $ret);
        }
        return $ret;
    }

    public function TopicClose($topicid){
        $topic = $this->Topic($topicid);
        if (empty($topicid)){
            return 404;
        }
        if (!$topic->IsCloseRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $statusid = ForumQuery::TopicStatusUpdate($this, $topic->id, ForumTopic::CLOSED);

        $ret = new stdClass();
        $ret->topicid = $topic->id;
        $ret->statusid = $statusid;

        $this->CacheClear();
        return $ret;
    }

    public function TopicOpenToJSON($topicid){
        $res = $this->TopicOpen($topicid);
        $ret = $this->ResultToJSON('TopicOpen', $res);

        if (!is_integer($res)){
            $ret = $this->ImplodeJSON($this->TopicToJSON($topicid), $ret);
        }
        return $ret;
    }

    public function TopicOpen($topicid){
        $topic = $this->Topic($topicid);
        if (empty($topicid)){
            return 404;
        }
        if (!$this->manager->IsWriteRole() || !$topic->IsOpenRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $statusid = ForumQuery::TopicStatusUpdate($this, $topic->id, ForumTopic::OPENED);

        $ret = new stdClass();
        $ret->topicid = $topic->id;
        $ret->statusid = $statusid;

        $this->CacheClear();
        return $ret;
    }

    public function TopicRemoveToJSON($topicid){
        $res = $this->TopicRemove($topicid);
        return $this->ResultToJSON('TopicRemove', $res);
    }

    public function TopicRemove($topicid){
        $topic = $this->Topic($topicid);
        if (empty($topicid)){
            return 404;
        }
        if (!$this->manager->IsWriteRole() || !$topic->IsRemoveRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $statusid = ForumQuery::TopicStatusUpdate($this, $topic->id, ForumTopic::OPENED);

        $ret = new stdClass();
        $ret->topicid = $topic->id;
        $ret->statusid = $statusid;

        $this->CacheClear();
        return $ret;
    }

    public function TopicToJSON($topicid){
        $res = $this->Topic($topicid, true);
        return $this->ResultToJSON('topic', $res);
    }

    /**
     * @param $topicid
     * @param bool|false $updateViewCount
     * @return ForumTopic|int
     */
    public function Topic($topicid, $updateViewCount = false){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        if (!isset($this->_cache['Topic'])){
            $this->_cache['Topic'] = array();
        }
        if (isset($this->_cache['Topic'][$topicid])){
            return $this->_cache['Topic'][$topicid];
        }

        $d = ForumQuery::Topic($this, $topicid);
        if (empty($d)){
            return 404;
        }

        /** @var ForumTopic $topic */
        $topic = $this->InstanceClass('Topic', $d);

        $statusList = $topic->statuses = $this->InstanceClass('TopicStatusList');
        $rows = ForumQuery::TopicStatusList($this, $topicid);
        while (($d = $this->db->fetch_array($rows))){
            $statusList->Add($this->InstanceClass('TopicStatus', $d));
        }

        $topic->status = $statusList->GetByIndex(0);

        $topic->commentStatistic = $this->CommentApp()->Statistic($topic->GetCommentOwner());

        $fileList = $topic->files = $this->InstanceClass('FileList');
        $rows = ForumQuery::TopicFileList($this, $topicid);
        while (($d = $this->db->fetch_array($rows))){
            $fileList->Add($this->InstanceClass('File', $d));
        }

        $notifyApp = $this->NotifyApp();

        $topic->notifyOwner = $notifyApp->OwnerById($topic->notifyOwnerId);

        if (Abricos::$user->id > 0){
            $topic->subscribe = $notifyApp->Subscribe($topic->notifyOwner);
        }

        if ($updateViewCount){
            ForumQuery::TopicViewCountUpdate($this, $topic);
        }

        return $this->_cache['Topic'][$topicid] = $topic;
    }

    /**
     * @param $rows
     * @return ForumTopicList
     */
    private function TopicListFill($rows){
        /** @var ForumTopicList $list */
        $list = $this->InstanceClass('TopicList');

        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Topic', $d));
        }

        $statusids = $list->ToArray('statusid');
        $rows = ForumQuery::TopicStatusListByIds($this, $statusids);
        while (($d = $this->db->fetch_array($rows))){
            /** @var ForumTopicStatus $status */
            $status = $this->InstanceClass('TopicStatus', $d);
            $topic = $list->Get($status->topicid);
            $topic->status = $status;
        }

        $topicids = $list->Ids();
        $statList = $this->CommentApp()->StatisticList('forum', 'topic', $topicids);
        $list->SetCommentStatistics($statList);
        return $list;
    }

    public function TopicListToJSON($page){
        $res = $this->TopicList($page);
        return $this->ResultToJSON('topicList', $res);
    }

    /**
     * @param $page
     * @param int $limit
     * @return ForumTopicList|int
     */
    public function TopicList($page, $limit = 30){
        $key = $page."_".$limit;
        if (!isset($this->_cache['TopicList'])){
            $this->_cache['TopicList'] = array();
        }
        if (isset($this->_cache['TopicList'][$key])){
            return $this->_cache['TopicList'][$key];
        }

        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $rows = ForumQuery::TopicList($this, $page, $limit);
        $list = $this->TopicListFill($rows);
        $list->page = $page;

        return $this->_cache['TopicList'][$key] = $list;
    }

    public function TopicListByIdsToJSON($topicids){
        $res = $this->TopicListByIds($topicids);
        return $this->ResultToJSON('topicList', $res);
    }

    public function TopicListByIds($topicids){
        if (!$this->manager->IsViewRole($topicids)){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        if (!is_array($topicids)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        $rows = ForumQuery::TopicListByIds($this, $topicids);
        $list = $this->TopicListFill($rows);

        return $list;
    }

    public function Comment_IsList($type, $ownerid){
        if (!$this->manager->IsViewRole()){
            return false;
        }
        if ($type != 'topic'){
            return false;
        }
        $topic = $this->Topic($ownerid);
        if (empty($topic)){
            return false;
        }
        // TODO: check for private topic
        return true;
    }

    public function Comment_IsWrite($type, $ownerid){
        if ($type != 'topic'){
            return false;
        }
        $topic = $this->Topic($ownerid);
        if (is_integer($topic)){
            return false;
        }
        return $topic->IsCommentWriteRole();
    }

    /**
     * @param $type
     * @param $ownerid
     * @param CommentStatistic $statistic
     */
    public function Comment_OnStatisticUpdate($type, $ownerid, $statistic){
        if ($type !== 'topic'){
            return;
        }
        $topic = $this->Topic($ownerid);
        if (is_integer($topic)){
            return;
        }

        ForumQuery::TopicCommentStatisticUpdate($this, $topic, $statistic);
    }

    /**
     * @param NotifyOwner $owner
     * @param NotifySubscribe $subscribe
     */
    public function Notify_IsSubscribeUpdate($owner, $subscribe){
        if (!$this->manager->IsViewRole()){
            return false;
        }
        if (
            ($owner->type === '' && $owner->method === '' && $owner->itemid === 0) ||
            ($owner->type === 'topic' && $owner->method === 'comment' && $owner->itemid === 0) ||
            ($owner->type === 'topic' && $owner->method === 'new' && $owner->itemid === 0)
        ){
            return true;
        }
        if ($owner->type === 'topic' && $owner->method === 'comment' && $owner->itemid > 0){
            return true;
        }

        return false;
    }
}

?>