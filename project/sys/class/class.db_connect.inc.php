<?php
	declare(strict_types=1);

	class DB_Connect {
		/**
		* Stores a database object
		*
		* @var object A database object
		*/
		protected $db;
		/**
		* Checks for a DB object or creates one if one isn't found
		*
		* @param object $db A database object
		*/
		protected function __construct($db=NULL)
		{
			if ( is_object($db) )
			{
				$this->db = $db;
			}
			else
			{
				// Constants are defined in /sys/config/db-cred.inc.php
				try
				{
					$this->db = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
				}
				catch ( Exception $e )
				{
					// If the DB connection fails, output the error
					die ( $e->getMessage() );
				}
			}
		}
	}