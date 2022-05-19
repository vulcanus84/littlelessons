<?php


define("level","./");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

require_once(level."inc/php/class_mail_littlelessons.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

$myMail = new littlelessons_mailer();
$myMail->add_recipient(1);
$myMail->add_text("Hello World!");
$myMail->send_mail();

?>