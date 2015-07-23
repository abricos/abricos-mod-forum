<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

if (empty(Abricos::$user->id)){
    return;
}

$modFM = Brick::$modules->GetModule('filemanager');
if (empty($modFM)){
    return;
}

$brick = Brick::$builder->brick;
$var = &$brick->param->var;

if (Abricos::$adress->dir[2] !== "go"){
    return;
}

$uploadFile = FileManagerModule::$instance->GetManager()->CreateUploadByVar('file');
$uploadFile->folderPath = "system/".date("d.m.Y", TIMENOW);
$error = $uploadFile->Upload();

if ($error == 0){
    $var['command'] = Brick::ReplaceVarByData($var['ok'], array(
        "fhash" => $uploadFile->uploadFileHash,
        "fname" => $uploadFile->fileName
    ));
} else {
    $var['command'] = Brick::ReplaceVarByData($var['error'], array(
        "errnum" => $error
    ));

    $brick->content = Brick::ReplaceVarByData($brick->content, array(
        "fname" => $uploadFile->fileName
    ));
}

?>