<?php
//*****************************************************************************
//26.03.2013 Claude Hübscher
//-----------------------------------------------------------------------------
//*****************************************************************************
class log
{
  function __construct()
  {
  }

	public function write_to_log($category, $text)
	{
    include(level.'inc/db.php');
    if(isset($_SESSION['login_user'])) { $log_user = $_SESSION['login_user']->fullname; } else { $log_user='Unknown'; }
  	$arr_fields = array('log_category'=>$category,'log_user'=>$log_user,'log_text'=>$text);
    $db->insert($arr_fields,'log');
	}
  
}

?>