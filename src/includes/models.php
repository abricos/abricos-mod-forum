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
        return
            ($this->app->manager->IsModerRole() || $this->userid == Abricos::$user->id)
            && $this->IsOpened();
    }

    public function IsCloseRole(){
        return $this->IsWriteRole();
    }

    public function IsRemoveRole(){
        return $this->IsWriteRole();
    }

    public function IsOpenRole(){
        return $this->IsClosed() && $this->app->manager->IsModerRole();
    }

    public function IsCommentWriteRole(){
        return
            $this->app->manager->IsWriteRole()
            && !$this->IsClosed() && !$this->IsRemoved();
    }
}

/**
 * Class ForumTopicList
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
 */
class ForumFile extends AbricosModel {
    protected $_structModule = 'forum';
    protected $_structName = 'File';
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