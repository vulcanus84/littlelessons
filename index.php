<?php

define("level","./");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
	$myPage->login_required=false;
	$myPage->set_title("Little Lessons");
	$myPage->add_content("<div class='row'>");
	$db->sql_query("SELECT * FROM courses");
	while($d = $db->get_next_res())
	{
		$myCourse = new course($d->course_id);
		$myPage->add_content($myCourse->get_card());
	}
	$myPage->add_content("</div>");


	print $myPage->get_html_code();


}
catch (Exception $e)
{
	$myPage = new page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}

?>