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
 * @property bool $isprivate
 * @property int $userid
 *
 * @property int $status
 * @property int $statuserid
 * @property int $statdate
 *
 * @property string $title
 * @property string $body
 * @property ForumFileList $files
 * @property CommentStatistic $commentStatistic
 * @property int $dateline
 *
 * @property ForumApp $app
 */
class ForumTopic extends AbricosModel {

    const ST_OPENED = 0;
    const ST_CLOSED = 1;
    const ST_REMOVED = 2;

    protected $_structModule = 'forum';
    protected $_structName = 'Topic';

    public function URI(){
        return "/forum/topic_".$this->id."/";
    }

    public function IsPrivate(){
        return $this->isprivate;
    }

    public function IsClosed(){
        return $this->status == ForumTopic::ST_CLOSED;
    }

    public function IsRemoved(){
        return $this->status == ForumTopic::ST_REMOVED;
    }

    public function IsCommentWrite(){
        return !$this->IsClosed() && !$this->IsRemoved();
    }

    public function IsWrite(){
        if ($this->IsClosed() || $this->IsRemoved()){
            return false;
        }

        if ($this->app->manager->IsModerRole()){
            return true;
        }

        if ($this->userid == Abricos::$user->id){
            return true;
        }

        return false;
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