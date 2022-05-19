<?php

define("level","../");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
    if(isset($_GET['verification_code']))
    {
        $db->sql_query("SELECT * FROM users WHERE user_verification_code=:code",array('code'=>$_GET['verification_code']));
        if($db->count()==1)
        {
            $d = $db->get_next_res();
            $myUser = new user($d->user_id);
            $myUser->set_status('Verified');
            $myUser->save();
            $db->update(array('user_verification_code'=>''),'users','user_id',$d->user_id);
            $myPage->add_content("Vielen Dank für die Verifizierung. Ihr Account ist nun aktiv.");
        }
        else
        {
            $myPage->error_text = "Code nicht gefunden";
        }
    }
    else
    {
        $myPage->error_text = "Kein Code angegeben";
    }
	print $myPage->get_html_code();


}
catch (Exception $e)
{
	$myPage = new page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}

?>