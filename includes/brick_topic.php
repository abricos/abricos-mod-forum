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
$user = $userList->Get($topic->userid);

$dl = $topic->dateline;
$tpFiles = "";
if ($topic->detail->fileList->Count()>0){
	$lstFile = "";
	for ($i=0;$i<$topic->detail->fileList->Count();$i++){
		$file = $topic->detail->fileList->GetByIndex($i);
		$lstFile .= Brick::ReplaceVarByData($v['file'], array(
			'url' => $file->URL(),
			'nm' => $file->name
		));
	}
	$tpFiles = Brick::ReplaceVarByData($v['files'], array(
		'rows' => $lstFile
	));
}

$replace = array(
	"status" => $v["st_".$topic->status],
	"uid" => $user->id,
	"unm" => $user->GetUserName(),
	"userurl" => $user->URL(),
	"files" => $tpFiles,
	"dl" => date("d", $dl)." ".rusMonth($dl)." ".date("Y", $dl),
	"dlt" => date("H:i", $dl),
	"tl" => $topic->title,
	"body" => $topic->detail->body
);

$brick->content = Brick::ReplaceVarByData($brick->content, $replace);

$meta_title = Brick::ReplaceVarByData($v['mtitle'], array(
		"title" => $topic->title,
		"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
		
));

Brick::$builder->SetGlobalVar('meta_title', $meta_title);

?>