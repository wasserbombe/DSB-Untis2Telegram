<?php
	error_reporting(E_ALL);

	// using Interdose/Dominik Deobald's DB class, available under MIT at https://github.com/Interdose/DB
	use Ids\DB AS DB;
	require_once('/var/www/inc/DB.php');
	
	// Prepare DB connection
	$_CONFIG = array(
		'database' => array(
			// database connection credentials
			'db' => array(
				'dsn' => 'mysql:dbname=vertretungsplan;host=localhost',
				'user' => 'vertretungsplan',
				'pass' => 'vertretungsplan'
			)
		),
		'telegram' => array(
			// token for telegram bot
			'token' => '%%YOUR TELEGRAM BOT TOKEN%%'
		),
		'dsb' => array(
			// credentials for "Digitales Schwarzes Brett" (DSB)
			'UserId' => '%%YOUR USER ID%%',
			'UserPw' => '%%YOUR USER PW%%'
		),
		'alerts' => array(
			array(
				// id of the telegram chat (bot must be added to this chat!); single user chats and groups are possible. 
				'chat_id' => 12345678,
				'name' => 'Name this chat to identify it later',
				// classes to search for
				'classes' => array("k1"),
				// subjects to search for 
				'subjects' => array("bio1","2rrk1","rrk1","rk","4ph1","ph1","m2","4m2","d2","4d2","2mu1","mu1","4f1","f","4e2","e2","2g2","g2","2psy1","psy1","2s1","s1","sp1","gk2","sf1","2b1","b1","2geo3","3s")
			),
			// ...
		)
	);
	$DB = new DB('db');
?>