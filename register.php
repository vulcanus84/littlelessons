<?php

define("level","./");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
	$myPage->login_required=false;
	$myPage->set_title("Little Lessons");
    $myPage->add_content("<div class='container-lg'>");
    $myPage->add_content("  <h3 class='my-3'>Registrieren</h3>");
    
    if(isset($_POST['firstname']))
    {
        $db->sql_query("SELECT * FROM users WHERE user_account=:account",array('account'=>$_POST['email']));
        if($db->count()>0)
        {
            $myPage->add_content("<span class='text-danger'>Die eingegebene E-Mail Adresse ".$_POST['email']." existiert bereits.</span>");
        }
        else
        {
            $my_user = new user();
            $my_user->set_firstname($_POST['firstname']);
			$my_user->set_lastname($_POST['lastname']);
			$my_user->set_gender($_POST['gender']);
			$my_user->set_email($_POST['email']);
            $my_user->save();
            $my_user->set_password($_POST['pw']);

            $myPage->add_content("<span class='text-success'>Vielen Dank für die Registrierung!<br/> Sie erhalten in den nächsten Minuten eine E-Mail zu Verifizierung.</span>");
        }

    }
    else
    {
        $myPage->add_content("  <form id='register' action='' method='POST' name='register' oninput='pw.setCustomValidity(pw_repeat.value != pw.value ? \"Passwörter sind nicht identisch.\" : \"\")'>");
        $myPage->add_content("      <div class='form-floating mb-3'>");
        $myPage->add_content("        <select class='form-select' id='gender' name='gender' required>");
        $myPage->add_content("          <option selected></option>");
        $myPage->add_content("          <option value='Herr'>Herr</option>");
        $myPage->add_content("          <option value='Frau'>Frau</option>");
        $myPage->add_content("        </select>");
        $myPage->add_content("        <label for='gender'>Anrede</label>");
        $myPage->add_content("      </div>");
        $myPage->add_content("      <div class='form-floating mb-3'>");
        $myPage->add_content("        <input type='text' class='form-control form-control-lg' id='firstname' name='firstname' required>");
        $myPage->add_content("        <label for='firstname'>Vorname</label>");
        $myPage->add_content("      </div>");
        $myPage->add_content("      <div  class='form-floating mb-3'>");
        $myPage->add_content("        <input type='text' class='form-control' id='lastname' name='lastname' required>");
        $myPage->add_content("        <label for='lastname' class='form-label'>Nachname</label>");
        $myPage->add_content("      </div>");
        $myPage->add_content("      <div class='form-floating mb-3'>");
        $myPage->add_content("        <input type='email' class='form-control' id='email' name='email' required>");
        $myPage->add_content("        <label for='email' class='form-label'>E-Mail Adresse</label>");
        $myPage->add_content("      </div>");
        $myPage->add_content("      <div class='form-floating mb-3'>");
        $myPage->add_content("        <input type='password' class='form-control' id='pw' name='pw' required>");
        $myPage->add_content("        <label for='pw' class='form-label'>Passwort</label>");
        $myPage->add_content("      </div>");
        $myPage->add_content("      <div class='form-floating mb-3'>");
        $myPage->add_content("        <input type='password' class='form-control' id='pw_repeat' name='pw_repeat' required>");
        $myPage->add_content("        <label for='pw_repeat' class='form-label'>Passwort wiederholen</label>");
        $myPage->add_content("      </div>");
        $myPage->add_content("      <button onclick='this.submit();' type='submit' class='btn btn-primary'>Registrieren</button>");
        $myPage->add_content("    </form>");
    }
    $myPage->add_content("  </div>");


	print $myPage->get_html_code();


}
catch (Exception $e)
{
	$myPage = new page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}

?>