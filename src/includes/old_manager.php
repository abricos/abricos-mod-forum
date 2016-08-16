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

        }

        if (empty($topic)){
            return null;
        }

        $topic = $this->Topic($sd->id, true);

        return $topic->id;
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


    ////////////////////////////// комментарии /////////////////////////////



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


}
