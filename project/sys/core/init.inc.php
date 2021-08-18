<?php
	
	declare(strict_types=1);

	/*
	* Enable sessions if needed.
	* Avoid pesky warning if session already active.
	*/
	$status = session_status();
	if ($status == PHP_SESSION_NONE){
		//There is no active session
		session_start();
	}
	/*
	* Generate an anti-CSRF token if one doesn't exist
	*/
	if ( !isset($_SESSION['token']) )
	{
		$_SESSION['token'] = sha1(uniqid((string)mt_rand(), TRUE));
	}
	/*
	* Include the necessary configuration info
	*/
	include_once '../sys/config/db-cred.inc.php';
	/*
	* Define constants for configuration info
	*/
	foreach ( $C as $name => $val )
	{
		define($name, $val);
	}
	
	/*
	* Define the auto-load function for classes
	*/
	function __autoload($class)
	{
		$filename = "../sys/class/class." . $class . ".inc.php";
		if ( file_exists($filename) )
		{
			include_once $filename;
		}
	}