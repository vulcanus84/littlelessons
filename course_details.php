<?php

define("level","./");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
	$myPage->login_required=false;
	$myPage->set_title("Little Lessons");
    if(isset($_GET['id']))
    {
        $my_course = new course($_GET['id']);
        $myPage->add_content($my_course->get_details());
    }
    else
    {
        $myPage->show_error("Kein Kurs ausgewählt");
    }
	$myPage->add_content("	</div>");
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