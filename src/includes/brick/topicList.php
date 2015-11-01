<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = &$brick->param->param;
$v = &$brick->param->var;

/** @var ForumModule $module */
$module = Abricos::GetModule('forum');

/** @var ForumApp $app */
$app = $module->GetManager()->GetApp();

$meta_title = $module->I18n()->Translate('title')." / ".SystemModule::$instance->GetPhrases()->Get('site_name');
Brick::$builder->SetGlobalVar('meta_title', $meta_title);

$topicList = $app->TopicList(1);
$topicList->FillUsers();

if ($topicList->Count() == 0){
    $brick->content = "";
    return;
}

$lst = "";
$count = $topicList->Count();

for ($i = 0; $i < $count; $i++){
    $topic = $topicList->GetByIndex($i);

    $replace = array(
        "removed" => $topic->IsRemoved() ? "removed" : "",
        "closed" => $topic->IsClosed() ? "closed" : "",
        "uri" => $topic->URI(),
        "title" => $topic->title,
        "user" => "",
        "dateline" => rusDateTime($topic->dateline),
        "cmt" => "0",
        "viewcount" => $topic->viewcount,
        "cmtuser" => "",
        "cmtdate" => rusDateTime($topic->dateline)
    );

    $user = $topic->user;
    if (!empty($user)){
        $replace['user'] = Brick::ReplaceVarByData($v['user'], array(
            "id" => $user->id,
            "viewname" => $user->GetViewName(),
            "uri" => $user->URI()
        ));
        $replace['cmtuser'] = $replace['user'];

    }

    $cmtStat = $topic->commentStatistic;
    if (!empty($cmtStat)){
        $user = $cmtStat->lastUser;
        if (!empty($user)){
            $replace['cmtuser'] = Brick::ReplaceVarByData($v['user'], array(
                "id" => $user->id,
                "viewname" => $user->GetViewName(),
                "uri" => $user->URI()
            ));
        }
        $replace['cmt'] = $cmtStat->count;
    }

    $lst .= Brick::ReplaceVarByData($v['row'], $replace);
}

$subscribeInfo = $app->SubscribeForumInfo();
if ($subscribeInfo === AbricosResponse::ERR_NOT_FOUND){
    $subscribe = $v['subscribe'];
} else {
    $subscribe = '';
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "subscribe" => $v['subscribe'],
    "rows" => $lst
));

?>