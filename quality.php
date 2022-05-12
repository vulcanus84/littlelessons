<?php

define("level","./");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
	$myPage->set_title("Little Lessons");
	$myPage->add_content("<div class='container-lg my-4'>");
	$myPage->add_content("	<div class='row justify-content-start mp-5'>");
	$db->sql_query("SELECT * FROM courses");
	while($d = $db->get_next_res())
	{
		$myPage->add_content(get_card($d->course_title,$d->course_description));
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

function get_card($title,$text)
{
  $txt = "";
  $txt.= "<div class='col-sm-6 col-xl-4 my-2'>";
  $txt.= "  <div class='card'>";
  $txt.= "    <div class='card-body'>";
  $txt.= "      <h5 class='card-title'>$title</h5>";
  $txt.= "      <p class='card-text'>$text</p>";
  $txt.= "      <a href='course_details.php' class='btn btn-primary'>Kurs anschauen</a>";
  $txt.= "    </div>";
  $txt.= "  </div>";
  $txt.= "</div>";
  return $txt;
}

?>