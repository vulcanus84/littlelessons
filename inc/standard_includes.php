<?php
  error_reporting(E_ALL);
  ini_set('max_execution_time','300'); //Set Timouts to 5 minutes, sometimes issues with mail-server response time...
	ini_set('session.gc_maxlifetime', 10800);    # 3 hours
	session_set_cookie_params(10800);

  header('Content-Type: text/html; charset=UTF-8');
  define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
  define('FILENAME',$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']);
  include_once(level."inc/php/BrowserDetection.php");       
	$browser = new BrowserDetection();
	
  define('BROWSER',strtoupper($browser->getName()));
  define('PLATFORM',strtoupper($browser->getPlatform()));

	$check=false;
	if(PLATFORM=='IPHONE') { $check = true; }
	if(PLATFORM=='IPAD') { $check = true; }
	if(PLATFORM=='IPOD') { $check = true; }

	if(BROWSER=='CHROME') { $check = true; }

  define('DATETIME_FIELD_SUPPORTED',$check);

  include_once(level."inc/php/class_log.php");
	include_once(level."inc/db.php");                      //Set DB connection to variable $db
  include_once(level."inc/php/class_translation.php");   //Load class for translation
  include_once(level."inc/php/class_user.php");          //Load object for user (MUST included before session start)

  session_start();
  include_once(level."inc/php/class_header_mod.php");    //Load class header_mod
  $page = new header_mod();                               //about the current page and header modification functions
  include_once(level."inc/php/class_page.php");          //Load class page
  include_once(level."inc/php/class_html.php");          //Load class for HTML Elements
  include_once(level."inc/php/class_menu.php");          //Load class menu
  include_once(level."inc/php/class_helper.php");        //Load class for helping functions
	include_once(level."inc/php/class_course.php");
	$helper = new helper();

	include_once(level."inc/settings.php");

?>