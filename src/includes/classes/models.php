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
 * @property string $title
 * @property string $body
 * @property ForumFileList $files
 * @property CommentStatistic $commentStatistic
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
        for ($i = 0; $i < count($list); $i++){
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