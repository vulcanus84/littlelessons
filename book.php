<?php

define("level","./");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	//print $_POST['appointment_id'];
	header("Location: course_details.php");
}
catch (Exception $e)
{
	print $e->getMessage();
}

?>