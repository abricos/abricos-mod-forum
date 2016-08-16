<?php
return array(
    "title" => "Форум",
    'roles' => array(
        '10' => 'Чтение',
        '30' => 'Запись',
        '40' => 'Модератор',
        '50' => 'Администратор'
    ),
    'groups' => array(
        "forum_moderator" => "Модератор форума"
    ),
    'brick' => array(
        'templates' => array(
            "1" => "Тема на форуме \"{v#tl}\"",
            "2" => "<p>
		Пользователь <b>{v#unm}</b> опубликовал(а) новую тему на форуме <a href='{v#plnk}'>{v#tl}</a>.
	</p>
	<p>Текст сообщения:</p>
	<blockquote>
		{v#prj}
	</blockquote>
	
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>",
            "3" => "Новый комментарий в теме форума \"{v#tl}\"",
            "4" => "<p>
		Пользователь <b>{v#unm}</b> написал(а) комментарий к сообщению 
		<a href='{v#plnk}'>{v#tl}</a>:
	</p>
	<blockquote>{v#cmt}</blockquote>
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>",
            "5" => "Ответ на ваш комментарий в сообщении \"{v#tl}\"",
            "6" => "<p>Пользователь <b>{v#unm}</b> ответил(а) на ваш комментарий в сообщении <a href='{v#plnk}'>{v#tl}</a>:</p>
	<blockquote>{v#cmt2}</blockquote>
	<p>Текст вашего комментария:</p>
	<blockquote>{v#cmt1}</blockquote>
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>",
            "7" => "Новый комментарий в сообщении \"{v#tl}\"",
            "8" => "<p>Пользователь <b>{v#unm}</b> написал(а) комментарий в сообщение <a href='{v#plnk}'>{v#tl}</a>:</p>
	<blockquote>{v#cmt}</blockquote>
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>"
        )

    ),
    'content' => array(
        'index' => array(
            "1" => "Идет загрузка форума ",
            "2" => "Пожалуйста, подождите..."
        ),
        'upload' => array(
            "1" => "Загрузка файла",
            "2" => "Выберите файл на своем компьютере",
            "3" => "Загрузить",
            "4" => "Идет загрузка файла, пожалуйста, подождите...",
            "5" => "Ну удалось загрузить файл",
            "6" => "Неизвестный тип файла",
            "7" => "Размер файла превышает допустимый",
            "8" => "Ошибка сервера",
            "9" => "Размер изображения превышает допустимый",
            "10" => "Недостаточно свободного места в вашем профиле",
            "11" => "Нет прав на загрузку файла",
            "12" => "Файл с таким именем уже загружен",
            "13" => "Необходимо выбрать файл для загрузки",
            "14" => "Некорректное изображение"
        )

    )
);
