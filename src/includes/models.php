<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class ForumTopic
 *
 * @property int $statusid
 *
 * @property int $userid
 * @property int $dateline
 * @property bool $isprivate
 *
 * @property string $title
 * @property string $body
 * @property string $pubkey
 *
 * @property int $viewcount
 * @property int $upddate
 *
 * @property ForumTopicStatus $status
 * @property ForumTopicStatusList $statuses
 * @property CommentStatistic $commentStatistic
 * @property ForumFileList $files
 *
 * @property ForumApp $app
 */
class ForumTopic extends AbricosModel {

    const OPENED = 'opened';
    const CLOSED = 'closed';
    const REMOVED = 'removed';

    protected $_structModule = 'forum';
    protected $_structName = 'Topic';

    /**
     * @var UProfileUser
     */
    public $user;

    public function URI(){
        return "/forum/topic_".$this->id."/";
    }

    public function IsPrivate(){
        return $this->isprivate;
    }

    public function IsOpened(){
        return $this->status->status === ForumTopic::OPENED;
    }

    public function IsClosed(){
        return $this->status->status === ForumTopic::CLOSED;
    }

    public function IsRemoved(){
        return $this->status->status === ForumTopic::REMOVED;
    }

    public function IsWriteRole(){
        if (!$this->app->manager->IsWriteRole()){
            return false;
        }
        return
            ($this->app->manager->IsModerRole() || $this->userid === Abricos::$user->id)
            && $this->IsOpened();
    }

    public function IsCloseRole(){
        return $this->IsWriteRole();
    }

    public function IsRemoveRole(){
        return $this->app->manager->IsModerRole() || $this->IsWriteRole();
    }

    public function IsOpenRole(){
        return $this->IsClosed() && $this->app->manager->IsModerRole();
    }

    public function IsCommentWriteRole(){
        return $this->app->manager->IsWriteRole() && $this->IsOpened();
    }

    private $_commentOwner;

    public function GetCommentOwner(){
        if (!empty($this->_commentOwner)){
            return $this->_commentOwner;
        }
        return $this->_commentOwner = $this->app->CommentApp()->InstanceClass('Owner', array(
            "module" => "forum",
            "type" => "topic",
            "ownerid" => $this->id
        ));
    }

    public function GetUserIds(){
        $ret = array();
        $ret[] = $this->userid;
        $ret[] = $this->status->userid;
        if (!empty($this->commentStatistic)){
            $ret[] = $this->commentStatistic->lastUserid;
        }
        return $ret;
    }

    /**
     * @param UProfileUserList $userList
     */
    public function FillUsers($userList = null){
        if (empty($userList)){
            $userids = $this->GetUserIds();
            $userList = $this->app->UProfileApp()->UserListByIds($userids);
        }
        $this->user = $userList->Get($this->userid);
        $this->status->user = $userList->Get($this->status->userid);
        if (!empty($this->commentStatistic)){
            $this->commentStatistic->lastUser = $userList->Get($this->commentStatistic->lastUserid);
        }
    }
}

/**
 * Class ForumTopicList
 *
 * @property ForumApp $app
 * @method ForumTopic Get($topicid)
 * @method ForumTopic GetByIndex($index)
 */
class ForumTopicList extends AbricosModelList {
    /**
     * @var int Number of Page
     */
    public $page = 1;

    /**
     * @param CommentStatisticList $list
     */
    public function SetCommentStatistics($list){
        for ($i = 0; $i < $list->Count(); $i++){
            $stat = $list->GetByIndex($i);
            $topic = $this->Get($stat->id);
            if (empty($topic)){
                continue; // what is it? %)
            }
            $topic->commentStatistic = $stat;
        }
    }

    public function FillUsers(){
        $count = $this->Count();
        $userids = array();
        for ($i = 0; $i < $count; $i++){
            $topic = $this->GetByIndex($i);
            $tUserids = $topic->GetUserIds();
            for ($ii = 0; $ii < count($tUserids); $ii++){
                $userids[] = $tUserids[$ii];
            }
        }

        $userList = $this->app->UProfileApp()->UserListByIds($userids);

        for ($i = 0; $i < $count; $i++){
            $topic = $this->GetByIndex($i);
            $topic->FillUsers($userList);
        }
    }

    public function ToJSON(){
        $ret = parent::ToJSON();
        $ret->page = $this->page;
        return $ret;
    }
}

/**
 * Class ForumTopicStatus
 *
 * @property int $topicid
 * @property string $status
 * @property int $userid
 * @property int $dateline
 */
class ForumTopicStatus extends AbricosModel {
    protected $_structModule = 'forum';
    protected $_structName = 'TopicStatus';

    /**
     * @var UProfileUser
     */
    public $user;
}

/**
 * Class ForumTopicStatusList
 * @method ForumTopicStatus Get($statusid)
 * @method ForumTopicStatus GetByIndex($index)
 */
class ForumTopicStatusList extends AbricosModelList {
}


/**
 * Class ForumFile
 *
 * @property int $topicid
 * @property string $filehash
 * @property string $filename
 * @property string $filesize
 */
class ForumFile extends AbricosModel {
    protected $_structModule = 'forum';
    protected $_structName = 'File';

    public function URI(){
        return '/filemanager/i/'.$this->filehash.'/'.$this->filename;
    }
}

/**
 * Class ForumFileList
 * @method ForumFile Get($fileid)
 * @method ForumFile GetByIndex($index)
 */
class ForumFileList extends AbricosModelList {
}

/**
 * Class ForumTopicConfig
 */
class ForumConfig extends AbricosModel {
    protected $_structModule = 'topic';
    protected $_structName = 'Config';
}

?>