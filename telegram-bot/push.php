<?php

/**
 * We can send message to users
 * 
 */

require_once("test_bot.php");

/// стандартые примеры уведомления

$msgs = ["Уведомляем вас о задолженности по оплате за электричество в размере 3457 рублей.",
"Срочное объявление! Просьба убрать машины со двора на 10.09 и 11.09, будет производиться ремонт полотна дороги",
"Уведомляем вас о том, что с 13.06.19 будет отключена горячая вода!"];


$bot = new TestBot();

// we can chose another
$users = [385078480];

$text = $msgs[0];

if ($_GET['message'] AND !empty($_GET['message']))
{
$text = $_GET['message'];	
}
else
	if($_GET['id'] AND !empty($_GET['id']) AND ($_GET['id']>=0) AND ($_GET['id']<=2))
	{
		$text = $msgs[$_GET['id']];
	}

$bot->mailing( $text, $users );


?>