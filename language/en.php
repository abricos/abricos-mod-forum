<?php
return array(
	"modtitle" => "Forum",
	'groups' => array(
		"forum_moderator" => "Moderator"
	)
,
	'brick' => array(
		'templates' => array(
			"1" => "New post on the forum \"{v#tl}\"",
			"2" => "<p><b>{v#unm}</b> has published a new post on the forum: <a href='{v#plnk}'>{v#tl}</a>.</p>
	<p>Topic:</p>
	<blockquote>
		{v#prj}
	</blockquote>
	
	<p>All the best,<br />{v#sitename}</p>",
			"3" => "New comment on \"{v#tl}\"",
			"4" => "<p><b>{v#unm}</b> add new comment to post <a href='{v#plnk}'>{v#tl}</a>:</p>
	<blockquote>{v#cmt}</blockquote>
	<p>All the best,<br />{v#sitename}</p>",
			"5" => "The answer to your comment in the message \"{v#tl}\"",
			"6" => "<p><b>{v#unm}</b> replied to your comment in the message <a href='{v#plnk}'>{v#tl}</a>:</p>
	<blockquote>{v#cmt2}</blockquote>
	<p>Comment:</p>
	<blockquote>{v#cmt1}</blockquote>
	<p>All the best,<br />{v#sitename}</p>",
			"7" => "New comment on \"{v#tl}\"",
			"8" => "<p><b>{v#unm}</b> add new comment to post <a href='{v#plnk}'>{v#tl}</a>:</p>
	<blockquote>{v#cmt}</blockquote>
	<p>All the best,<br />{v#sitename}</p>"
		)

	)
,
	'content' => array(
		'index' => array(
			"1" => "Loading Forum",
			"2" => "Please, wait..."
		)
,
		'upload' => array(
			"1" => "File Upload",
			"2" => "Select a file to your computer",
			"3" => "Upload",
			"4" => "Uploading file. Please, wait...",
			"5" => "Well could upload a file",
			"6" => "Unknown file type",
			"7" => "File size exceeds the allowable",
			"8" => "Server Error",
			"9" => "The image size exceeds the allowable",
			"10" => "Not enough free space on your profile",
			"11" => "No rights to download it",
			"12" => "A file with that name is already loaded",
			"13" => "You must select a file to upload",
			"14" => "Incorrect image"
		)

	)
);
?>