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

$man = ForumModule::$instance->GetManager();

$mList = $man->TopicList();

if ($mList->Count() == 0){
    $brick->content = "";
    return;
}

$userList = $man->UserList($mList->userIds);

$lst = "";
for ($i = 0; $i < $mList->Count(); $i++){
    $msg = $mList->GetByIndex($i);

    $user = $userList->Get($msg->lastUserId);
    if (empty($user)){
        $user = $userList->Get($msg->userid);
    }

    $lst .= Brick::ReplaceVarByData($v['row'], array(
        "removed" => $msg->IsRemoved() ? "removed" : "",
        "closed" => $msg->IsClosed() ? "closed" : "",
        "uri" => $msg->URI(),
        "tl" => $msg->title,
        "cmtdate" => rusDateTime($msg->lastCommentDate ? $msg->lastCommentDate : $msg->dateline),
        "cmt" => $msg->commentCount,
        "cmtuser" => Brick::ReplaceVarByData($v['user'], array(
            "uid" => $user->id,
            "unm" => $user->GetUserName(),
            "url" => $user->URL()
        ))
    ));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "result" => Brick::ReplaceVarByData($v['table'], array(
        "rows" => $lst
    ))
));
?>