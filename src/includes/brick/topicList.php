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

/** @var ForumApp $app */
$app = Abricos::GetModule('forum')->GetManager()->GetApp();

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

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "rows" => $lst
));
?>