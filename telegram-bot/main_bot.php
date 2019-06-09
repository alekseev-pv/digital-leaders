<?php

require_once("lib/telegram_bot.php");

$question = 7;
$address = 8;
$phone = 9;


$phone_file = "phone.txt";

$addr_file = "phone.txt";

$qes_file = "qes.txt";


function json_send($url, $arr)
{
	
$payload = json_encode($arr);
 
// Prepare new cURL resource
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
 
// Set HTTP Header for POST request 
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload))
);
 
// Submit the POST request
$result = curl_exec($ch);
 
// Close cURL session handle
curl_close($ch);

return $result;
}


function set_state($state)
{
 @unlink("state.txt");	
 @file_put_contents("state.txt", $state, FILE_APPEND);	
}

function get_state()
{
$state = "0";

if (file_exists("state.txt"))
{
 $state = @file_get_contents("state.txt");	
}

return $state;
}


class TestBot extends TelegramBot{

	//protected $token = "";
	protected $token = "";

	/**
	 * Fill you bot name if you want to use it in groups
	 * @example "@my_test_bot"
	 */
	protected $bot_name = "@digitalp_bot";

	/**
	 * Предустановленные варианты команд
	 * команда => метод для обработки команды
	 */
	protected $commands = [
			"/start" => "cmd_start",
			"/help" => "cmd_help",
			"Новости" => "cmd_novosti",
			"Инлайн" => "cmd_inlinemenu",
			
			"Заявка" => "cmd_zayavka",
			"Вопрос" => "cmd_vopros",
			"Отзыв" => "cmd_otziv",
			
			"Квартира" => "cmd_kv",
			"Дом" => "cmd_do",
			"Двор" => "cmd_dv",
			"Другое" => "cmd_dr"

		];

	/**
	 * Предустановленные клавиатуры
	 *
	 * Справка по клавитурам: https://core.telegram.org/bots/api#replykeyboardmarkup
	 * 
	 */
	public $keyboards = [
		'start' => [
			'keyboard' => [
				["Заявка"],
				["Вопрос"],
				["Отзыв"]
			]
		],
		'default' => [
			'keyboard' => [
				["Привет", "Новости"], // Две кнопки в ряд
				["Инлайн меню"] // Кнопка на всю ширину
			]
		],
		'inline' => [
			// Две кнопки в ряд
			[
				// вызовет метод callback_act1(),
				[
					'text' => "➡ Квартира",
					'callback_data'=> "act1"
				],
				[
					'text' => "➡ Дом",
					'callback_data'=> "act2"
				]
			],
			[
				['text' => "➡ Двор", 'callback_data'=> "act3"],
				['text' => "➡ Другое", 'callback_data'=> "act4"]
			],
			// Кнопка на всю ширину
			[
				['text' => "↩ Назад", 'callback_data'=> "back"], // 'text' => "🚪 Закрыть", 'callback_data'=> "logout"
			]
		],
		'jobs' => [
			// Две кнопки в ряд
			[
				// вызовет метод callback_act1(),
				[
					'text' => "➡ Сантехника",
					'callback_data'=> "act11"
				],
				[
					'text' => "➡ Электрика",
					'callback_data'=> "act21"
				]
			],
			[
				['text' => "➡ Клининг", 'callback_data'=> "act31"],
				['text' => "➡ Другое", 'callback_data'=> "act41"]
			],
			// Кнопка на всю ширину
			[
				['text' => "↩ Назад", 'callback_data'=> "back"], // 'text' => "🚪 Закрыть", 'callback_data'=> "logout"
			]
		],
		'back' =>[[['text' => "↩ Назад", 'callback_data'=> "back"]]]
	];

	/**
	 * Обработка ввода команды "/start"
	 */
	function cmd_start(){
		$this->api->sendMessage([
			'text' => "Я бот-помощник в ЖКХ сфере!\r\nВы можете выбрать пункт меню или написать запрос человеческим языком.\r\nВ случае серьёзных аварий звоните 112\r\n",
			'reply_markup' => json_encode($this->keyboards['start'])
		]);
	}

	/**
	 * Обработка ввода команды "Привет"
	 */
	function cmd_privet(){
		$this->api->sendMessage( "И тебе привет, @" . $this->result["message"]["from"]["username"] . "." );
	}

	/**
	 * Обработка ввода команды "Картинка" отправляет сообщение с картинкой
	 */
	function cmd_kartinka(){
		$this->api->sendPhoto( "https://webportnoy.ru/upload/alno/alno3.jpg", "Описание картинки" );
	}

	/**
	 * Обработка ввода команды "Гифка" отправляет сообщение с гифкой
	 */
	function cmd_gifka(){
		$this->api->sendDocument( "https://webportnoy.ru/upload/1.gif", "Описание гифки" );
	}

	/**
	 * Обработка ввода команды "Новости" отправляет сообщение со списком новостей из RSS-ленты
	 */
	function cmd_novosti(){
		$rss = simplexml_load_file('http://vposelok.com/feed/1001/');
		$text = "";
		foreach( $rss->channel->item as $item ){
			$text .= "\xE2\x9E\xA1 " . $item->title . " (<a href='" . $item->link . "'>читать</a>)\n\n";
		}
		$this->api->sendMessage([
			'parse_mode' => 'HTML', 
			'disable_web_page_preview' => true, 
			'text' => $text 
		]);
	}

	/**
	 * Обработка ввода команды "Музыка" отправляет сообщение с аудиофайлом
	 * 20 Mb maximum: https://core.telegram.org/bots/api#sending-files
	 */
	function cmd_music(){
		$url = "http://vposelok.com/files/de-phazz_-_strangers_in_the_night.mp3";
		$this->api->sendAudio( $url );
	}

	/**
	 * Обработка ввода команды "Подкаст" отправляет сообщение с последним выпуском подкаста
	 * Если файл подкаста меньше 20 Мб, то он будет отправлен сообщением, в противном случае будет добавлена ссылка на скачивание.
	 * 20 Mb maximum: https://core.telegram.org/bots/api#sending-files
	 */
	function cmd_podcast(){
		$rss = simplexml_load_file('https://meduza.io/rss/podcasts/tekst-nedeli');

		$item = $rss->channel->item;
		$enclosure = (array) $item->enclosure;
		$size = round( $enclosure['@attributes']['length'] / (1024*1024), 1 );
		$text = "🎙 {$item->title}";

		if( $size < 20 ){
			$this->api->sendAudio( $enclosure['@attributes']['url'] );
		}
		else{
			$text .= "\n\n⬇️ <a href='" . $enclosure['@attributes']['url'] . "'>скачать</a> {$size}Mb";
		}

		$this->api->sendMessage([
			'parse_mode' => 'HTML', 
			'disable_web_page_preview' => true, 
			'text' => $text 
		]);
	}




	/**
	 * Ответ по-умолчанию
	 */
	function cmd_default(){

		$t = get_state();
		
		// у нас вопрос, пишем его в файл. спрашиваем телефон
		
	if ($t == 7)
		{
			// write question to file
			@file_put_contents("question.txt", $this->result["message"]["text"]);
			
			set_state(8);
			
		$this->api->sendMessage([
			'text'=>"Введите ваш телефон :"
		]);
		
		return;	
		
		}	

if ($t == 8)
{
			// write phone to file
		@file_put_contents("phone.txt", $this->result["message"]["text"]);

$data = array(
'type' => 'vopros',
'phone' => @file_get_contents("phone.txt"),
'vopros' => @file_get_contents("question.txt")
);


$answer = json_send('http://159.69.18.145/api/question', $data);

$answer = 49;


		$this->api->sendMessage([
			'text'=>"Спасибо за вопрос, мы свяжемся с вами в ближайшее время!\r\nВаш номер заявки : ".$answer."\r\nВаши данные, телефон : ".@file_get_contents("phone.txt") . "\r\nВопрос : " . @file_get_contents("question.txt")
		]);


		set_state(1);
		
return;	
}			


	if ($t == 10)
		{
		// write phone to file
		@file_put_contents("address.txt", $this->result["message"]["text"]);

		$this->api->sendMessage([
			'text'=>"Введите телефон: "
		]);		
		
	    set_state(11);
		return;	
		}


if ($t == 11)
{
	
// write phone to file
	@file_put_contents("phone.txt", $this->result["message"]["text"]);
		
$data = array(
'type' => 'zayavka',
'phone' => @file_get_contents("phone.txt"),
'address' => @file_get_contents("address.txt"),
'object-type' => @file_get_contents("object.txt"),
'job-type' => @file_get_contents("job.txt")
);

$answer = json_send('http://159.69.18.145/api/request', $data);

$this->api->sendMessage([
	'text'=>"Мы свяжемся с вами в ближайшее время!\r\nВаш номер заявки : ".$answer."\r\nВаши данные, телефон : ".@file_get_contents("phone.txt")."\r\nАдрес : ".@file_get_contents("address.txt")."\r\nТип объекта : ".@file_get_contents("object.txt")."\r\nВид работы : ".@file_get_contents("job.txt")
]);
	
return;	

}


if ($t == 17)
{
	// write phone to file
		@file_put_contents("feedback.txt", $this->result["message"]["text"]);
			
			set_state(18);
		$this->api->sendMessage([
			'text'=>"Введите ваш телефон:"
		]);
return;	
}

if ($t == 18)
{
	// write phone to file
		@file_put_contents("phone.txt", $this->result["message"]["text"]);
			
			set_state(1);
		$this->api->sendMessage([
			'text'=>"Спасибо за отзыв!"
		]);
return;	

}


		// Ответ на сообщения содержащих слово тариф. Например "Расскажи мне о тарифах" или "Какие есть тарифы?"
		if( stripos( $this->result["message"]["text"], "тариф" ) !== false ){
			$this->api->sendMessage( "Тариф1: 123\nТариф2: 234\nТариф3: 345" );
		}
		// Если пользователь хочет поддержки
		elseif( stripos( $this->result["message"]["text"], "поддержк" ) !== false ){
			$this->api->sendMessage( "Техническая поддержка пока доступна только по email." );
		}
		// Если не сработали никакие команды
		else{
			$this->api->sendMessage([
				'text' => "Не знаю что ответить, не научили меня еще таким командам. Могу показать структуру сообщения:\n<pre>" . print_r( $this->result, 1) . "</pre>",
				'parse_mode'=> 'HTML'
			]);
		}
	}
	
	function cmd_kv()
	{
		@file_put_contents("object.txt", "Квартира");
		$this->api->sendMessage([
			'text'=>"Выберите вид работ :",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['jobs']
			] )
		]);
	}

	function cmd_do()
	{
		@file_put_contents("object.txt", "Дом");
		$this->api->sendMessage([
			'text'=>"Выберите вид работ :",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['jobs']
			] )
		]);		
	}

	function cmd_dv()
	{
		@file_put_contents("object.txt", "Двор");
		$this->api->sendMessage([
			'text'=>"Выберите вид работ :",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['jobs']
			] )
		]);			
	}

	function cmd_dr()
	{
		@file_put_contents("object.txt", "Другое");
		$this->api->sendMessage([
			'text'=>"Выберите вид работ :",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['jobs']
			] )
		]);	
	}	

	

	/**
	 * Обработка ввода команды "Инлайн" отправляет сообщение с клавиатурой, прикрепелнной к сообщению.
	 */
	function cmd_inlinemenu(){
		$this->api->sendMessage([
			'text'=>"Ниже выведены кнопки, нажатие на которые может выполнять какие-то действия. Бот не ответит на кнопке будет отображаться иконка часиков.",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['inline']
			] )
		]);
	}

/// заявка
	function cmd_zayavka(){
		$this->api->sendMessage([
			'text'=>"Выберите объект :",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['inline']
			] )
		]);
	}	

/// вопрос
	function cmd_vopros(){
		
		$this->api->sendMessage([
			'text'=>"Введите ваш вопрос: "
		]);	
		
		set_state(7);
	}	
	
	
/// отзыв
	function cmd_otziv(){
		
		$this->api->sendMessage([
			'text'=>"Введите ваш отзыв: "
		]);	
		
		set_state(17);
	}	
	

	
	// квартира
	function callback_act1(){
		@file_put_contents("object.txt", "Квартира");
		$this->api->sendMessage([
			'text'=>"Выберите вид работ :",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['jobs']
			] )
		]);
	}

	// Ответ на нажатие кнопки с обработкой дополнительных параметров
	function callback_act2(){
		@file_put_contents("object.txt", "Дом");
		$this->api->sendMessage([
			'text'=>"Выберите вид работ :",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['jobs']
			] )
		]);	
	}

	// Ответ на нажатие кнопки всплывающим окном
	function callback_act3(){
		@file_put_contents("object.txt", "Двор");
		$this->api->sendMessage([
			'text'=>"Выберите вид работ :",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['jobs']
			] )
		]);	
	}

	// Ответ на нажатие кнопки всплывающим окном
	function callback_act4(){
		@file_put_contents("object.txt", "Другое");
		$this->api->sendMessage([
			'text'=>"Выберите вид работ :",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['jobs']
			] )
		]);	
	}
	


/////////////////////////job types

	// Сантехника
	function callback_act11(){
		@file_put_contents("job.txt", "Сантехника");
		set_state(10);
		$this->api->sendMessage([
			'text'=>"Укажите адрес :"
		]);		
	}

	// Ответ на нажатие кнопки с обработкой дополнительных параметров
	function callback_act21(){
		@file_put_contents("job.txt", "Электрика");
		set_state(10);
		$this->api->sendMessage([
			'text'=>"Укажите адрес :"
		]);		
	}

	// Ответ на нажатие кнопки всплывающим окном
	function callback_act31(){
		@file_put_contents("job.txt", "Клининг");
		set_state(10);
		$this->api->sendMessage([
			'text'=>"Укажите адрес :"
		]);		
	}

	// Ответ на нажатие кнопки всплывающим окном
	function callback_act41(){
		@file_put_contents("job.txt", "Другое");
		set_state(10);
		$this->api->sendMessage([
			'text'=>"Укажите адрес :"
		]);		
	}


///////////////////////////end job types


	// Ответ на кнопку "Назад" выводит начальную клавиатуру
	function callback_back(){
		$text = "Вы вернулись к выбору объекта заявки";
		$this->api->deleteMessage( $this->result['callback_query']['message']['message_id'] );
		$this->callbackAnswer( $text, $this->keyboards['inline'] );
	}

	// Ответ на кнопку "Закрыть"
	function callback_logout(){
		$this->api->answerCallbackQuery( $this->result['callback_query']["id"] );
		$this->api->deleteMessage( $this->result['callback_query']['message']['message_id'] );
	}

}

?>
