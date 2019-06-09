<?php

/**
 * Use this file for webhook. It will reply any command from users
 * 
 */

require_once("main_bot.php");

$bot = new MainBot();
$bot->replyCommand();

?>