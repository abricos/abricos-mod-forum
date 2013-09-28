<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = &$brick->param->param;
$v = &$brick->param->var;

$topic = ForumModule::$instance->currentTopic;
if (empty($topic)){
	$brick->content = "";
	return;
}

$man = ForumModule::$instance->GetManager();

$userList = $man->UserList($topic);

$replace = array(
	"tl" => $topic->title,
	"body" => $topic->detail->body
);
/*
$user = $userList->Get($topic->lastUserId);
if (empty($user)){
	$user = $userList->Get($topic->userid);
}

$lst .= Brick::ReplaceVarByData($v['row'], array(
	"removed" => $topic->IsRemoved() ? "removed" : "",
	"closed" => $topic->IsClosed() ? "closed" : "",
	"uri" => $topic->URI(),
	"tl" => $topic->title,
	"cmtdate" => rusDateTime($topic->lastCommentDate ? $topic->lastCommentDate : $topic->dateline),
	"cmt" => $topic->commentCount,
	"cmtuser" => Brick::ReplaceVarByData($v['user'], array(
		"uid" => $user->id,
		"unm" => $user->GetUserName(),
		"url" => $user->URL()
	))
));
/**/

$brick->content = Brick::ReplaceVarByData($brick->content, $replace);
?>