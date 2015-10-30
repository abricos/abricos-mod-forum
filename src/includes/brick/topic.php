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

$adress = Abricos::$adress;

$topicid = 0;
if (isset($adress->dir[1])){
    $a = explode("_", $adress->dir[1]);

    if ($a[0] === 'topic'){
        $topicid = intval($a[1]);
    }
}

$topic = $app->Topic($topicid, true);

if (is_integer($topic)){
    $brick->content = "";
    return;
}

$topic->FillUsers();

$meta_title = $topic->title." / ".$module->I18n()->Translate('title')." / ".SystemModule::$instance->GetPhrases()->Get('site_name');
Brick::$builder->SetGlobalVar('meta_title', $meta_title);

$replace = array(
    "title" => $topic->title,
    "userURI" => "",
    "userName" => "",
    "files" => "",
    "status" => $v[$topic->status->status],
    "dateline" => rusDateTime($topic->dateline),
);

$user = $topic->user;
if (!empty($user)){
    $replace["userURI"] = $user->URI();
    $replace["userName"] = $user->GetViewName();
    $replace["userAvatar"] = $user->GetAvatar45();
}
$files = $topic->files;
if (!empty($files)){
    $lst = "";
    for ($i = 0; $i < $files->Count(); $i++){
        $file = $files->GetByIndex($i);
        $lst .= Brick::ReplaceVarByData($v['file'], array(
            "nm" => $file->filename,
            "uri" => $file->URI()
        ));
    }
    $replace['files'] = Brick::ReplaceVarByData($v['files'], array(
        "list" => $lst
    ));

}

$replace["topicbody"] = $topic->body;
$brick->content = Brick::ReplaceVarByData($brick->content, $replace);

Brick::$builder->LoadBrickS('comment', 'tree', $brick, array(
    "p" => array(
        "module" => 'forum',
        "type" => 'topic',
        "ownerid" => $topic->id
    )
));


?>