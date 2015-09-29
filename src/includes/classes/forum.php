<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'models.php';

/**
 * Class Forum
 *
 * @property ForumManager $manager
 */
class Forum extends AbricosApplication {

    const ST_OPENED = 0;
    const ST_CLOSED = 1;
    const ST_REMOVED = 2;

    protected function GetClasses(){
        return array(
            'Topic' => 'ForumTopic',
            'TopicList' => 'ForumTopicList',
            'File' => 'ForumFile',
            'FileList' => 'ForumFileList',
        );
    }

    protected function GetStructures(){
        return 'Topic,TopicList,File,FileList';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'topicList':
                return $this->TopicListToJSON($d->page);
            case 'topic':
                return $this->TopicToJSON($d->topicid);
            case 'topicSave':
                return $this->TopicSaveToJSON($d->topic);
            case 'topicClose':
                return $this->TopicCloseToJSON($d->topicid);
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
        return $this->ResultToJSON('topicSave', $res);
    }

    public function TopicSave($d){
        if (!$this->manager->IsWriteRole()){
            return 403;
        }

        $d->id = intval($d->id);
        $d->isprivate = 0;

        $utmf = Abricos::TextParser(true);
        $utm = Abricos::TextParser();

        $d->title = $utmf->Parser($d->title);
        $d->body = $utm->Parser($d->body);

        $sendNewNotify = false;

        if ($d->id === 0){
            $pubkey = md5(time().Abricos::$user->id);
            $d->id = ForumQuery::TopicAppend($this->db, $d, $pubkey);

            $sendNewNotify = true;
        }

        $this->CacheClear();
        $topic = $this->Topic($d->id);

        if (empty($topic)){
            return 500;
        }

        // обновить информацию по файлам
        $fileList = $topic->files;
        $arr = $d->files;

        for ($i = 0; $i < $fileList->Count(); $i++){
            $cFile = $fileList->GetByIndex($i);
            $find = false;
            foreach ($arr as $file){
                if ($file->id == $cFile->id){
                    $find = true;
                    break;
                }
            }
            if (!$find){
                ForumQuery::TopicFileRemove($this->db, $d->id, $cFile->id);
            }
        }
        foreach ($arr as $file){
            $find = false;
            for ($i = 0; $i < $fileList->Count(); $i++){
                $cFile = $fileList->GetByIndex($i);
                if ($file->id == $cFile->id){
                    $find = true;
                    break;
                }
            }
            if (!$find){
                ForumQuery::TopicFileAppend($this->db, $d->id, $file->id, $this->userid);
            }
        }

        if ($sendNewNotify){
            // Отправить уведомление всем модераторам

            $brick = Brick::$builder->LoadBrickS('forum', 'templates', null, null);
            $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
            $plnk = "http://".$host.$topic->URI();

            $rows = ForumQuery::ModeratorList($this->db);
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

    public function TopicToJSON($topicid){
        $res = $this->Topic($topicid);
        return $this->ResultToJSON('topic', $res);
    }

    /**
     * @param $topicid
     * @return ForumTopic
     */
    public function Topic($topicid){
        if (!$this->manager->IsViewRole()){
            return 403;
        }

        if (!isset($this->_cache['Topic'])){
            $this->_cache['Topic'] = array();
        }
        if (isset($this->_cache['Topic'][$topicid])){
            return $this->_cache['Topic'][$topicid];
        }

        $d = ForumQuery::Topic($this->db, $topicid);
        if (empty($d)){
            return 404;
        }
        /** @var ForumTopic $topic */
        $topic = $this->models->InstanceClass('Topic', $d);

        $rows = ForumQuery::TopicFileList($this->db, $topicid);
        while (($d = $this->db->fetch_array($rows))){
            $topic->files->Add($this->models->InstanceClass('File', $d));
        }

        return $this->_cache['Topic'][$topicid] = $topic;
    }

    public function TopicListToJSON($page){
        $res = $this->TopicList($page);
        return $this->ResultToJSON('topicList', $res);
    }

    /**
     * @return TopicList
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
            return 403;
        }

        /** @var TopicList $list */
        $list = $this->models->InstanceClass('TopicList');
        $list->page = $page;

        $rows = ForumQuery::TopicList($this->db, $page, $limit);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->models->InstanceClass('Topic', $d));
        }
        return $this->_cache['TopicList'][$key] = $list;
    }
}

?>