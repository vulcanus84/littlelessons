<?php

define("level","./");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
	$myPage->permission_required=false;
    $myPage->add_js_link(level."inc/ckeditor/ckeditor.js");

	if(isset($_SESSION['login_user']))
	{
		$message = ""; $error = "";
		
		$myPage->add_content_with_translation("<h3 class='my-3'>Meine Kurse</h3>");
		if($message!='') { $myPage->add_content($myPage->show_info($myPage->t->translate($message))); }
		if($error!='') { $myPage->add_content($myPage->show_error($myPage->t->translate($error))); }
		if(isset($_GET['course_id']))
        {
            $myCourse = new course($_GET['course_id']);
            $myPage->add_content("<div class='container-lg'>");
            $myPage->add_content("<a href='my_courses.php' class='btn btn-primary '>Zurück</a>");

            $myPage->add_content("
            <div class='accordion mt-3' id='accordionExample'>
            <div class='accordion-item'>
            <h2 class='accordion-header' id='headingFour'>
                <button class='accordion-button' type='button' data-bs-toggle='collapse' data-bs-target='#collapseFour' aria-expanded='false' aria-controls='collapseFour'>
            ");
$myPage->add_content_with_translation("Beschreibung");
$myPage->add_content("
                    </button>
                </h2>
                <div id='collapseFour' class='accordion-collapse collapse show' aria-labelledby='headingFour' data-bs-parent='#accordionExample'>
                    <div class='accordion-body'>
                    ");
$myPage->add_content("<form id='personals' action='".$page->change_parameter('action','save_personals')."' method='POST' >");

$myPage->add_content("      <div class='form-floating mb-3'>");
$myPage->add_content("        <input type='text' class='form-control' id='title' name='title' value='".$myCourse->title."' required>");
$myPage->add_content("        <label for='title'>Titel</label>");
$myPage->add_content("      </div>");
$myPage->add_content("      <div  class='form-floating mb-3'>");
$myPage->add_content("        <textarea class='form-control' id='description' name='description' style='height:300px;' required>".$myCourse->description."</textarea>");
$myPage->add_content("      </div>");
$myPage->add_content("      <div class='form-floating mb-3'>");
$myPage->add_content("        <input type='email' class='form-control' id='email' name='email' value='".$_SESSION['login_user']->email."' required>");
$myPage->add_content("        <label for='email' class='form-label'>E-Mail Adresse</label>");
$myPage->add_content("      </div>");
$myPage->add_content_with_translation("<button onclick='this.submit();' class='btn btn-primary'>Speichern</button></td>");
$myPage->add_content("</form>");
            

$myPage->add_content("
</div>
</div>
</div>
                <div class='accordion-item'>
            <h2 class='accordion-header' id='headingOne'>
                <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapseOne' aria-expanded='true' aria-controls='collapseOne'>
        ");
$myPage->add_content_with_translation("Bild wechseln");
$myPage->add_content("
                </button>
            </h2>
            <div id='collapseOne' class='accordion-collapse collapse' aria-labelledby='headingOne' data-bs-parent='#accordionExample'>
                <div class='accordion-body'>
        ");
$myPage->add_content("<form id='new_user' action='".$page->change_parameter('action','change_pic')."' method='post' enctype='multipart/form-data'>");
$myPage->add_content("<input type='hidden' id='user_id' name='user_id' value='".$_SESSION['login_user']->id."' />");
$myPage->add_content($_SESSION['login_user']->get_picture(null,'upload_pic','150px',false));
$myPage->add_content("<input style='visibility:hidden;' onchange='$(\"#new_user\").submit();' name='pictures[]' id='inpPicture' type='file' accept='image/*'/>");
$myPage->add_content("</form>");
$myPage->add_content_with_translation("<a class='btn btn-primary mt-3' onclick='upload_pic();' role='button'>Bild wechseln</a>");
$myPage->add_content_with_translation("<a class='btn btn-warning mt-3' href='?action=delete_pic' role='button'>Bild löschen</a>");

$myPage->add_content("
        </div>
        </div>
        </div>
        <div class='accordion-item'>
        <h2 class='accordion-header' id='headingTwo'>
            <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapseTwo' aria-expanded='false' aria-controls='collapseTwo'>
        ");
$myPage->add_content_with_translation("Passwort");
$myPage->add_content("
                </button>
            </h2>
            <div id='collapseTwo' class='accordion-collapse collapse' aria-labelledby='headingTwo' data-bs-parent='#accordionExample'>
                <div class='accordion-body'>
                ");
$myPage->add_content("<form id='change_password' action='".$page->change_parameter('action','change_password')."' method='POST' oninput='new_password.setCustomValidity(new_password_repeat.value != new_password.value ? \"Passwörter sind nicht identisch.\" : \"\")'>");

$myPage->add_content("      <div class='form-floating mb-3'>");
$myPage->add_content("        <input type='password' class='form-control' id='old_password' name='old_password' required>");
$myPage->add_content("        <label for='old_password' class='form-label'>Altes Passwort</label>");
$myPage->add_content("      </div>");
$myPage->add_content("      <div class='form-floating mb-3'>");
$myPage->add_content("        <input type='password' class='form-control' id='new_password' name='new_password' required>");
$myPage->add_content("        <label for='new_password' class='form-label'>Neues Passwort</label>");
$myPage->add_content("      </div>");
$myPage->add_content("      <div class='form-floating mb-3'>");
$myPage->add_content("        <input type='password' class='form-control' id='new_password_repeat' name='new_password_repeat' required>");
$myPage->add_content("        <label for='new_password_repeat' class='form-label'>Neues Passwort wiederholen</label>");
$myPage->add_content("      </div>");
$myPage->add_content_with_translation("<button onclick='this.submit();' class='btn btn-primary'>Passwort wechseln</button></td>");
$myPage->add_content("</form>");
        

$myPage->add_content("
</div>
</div>
</div>
<div class='accordion-item'>
<h2 class='accordion-header' id='headingThree'>
<button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapseThree' aria-expanded='false' aria-controls='collapseThree'>");
$myPage->add_content_with_translation("Sprache");
$myPage->add_content("
</button>
</h2>
<div id='collapseThree' class='accordion-collapse collapse' aria-labelledby='headingThree' data-bs-parent='#accordionExample'>
<div class='accordion-body'>");
$myPage->add_content("<table><tr>");
$page->reset();
$page->remove_parameter('action');
$page->change_parameter('change_language','german');
$txt = "<a href='".$page->get_link()."'><img style='"; if($_SESSION['login_user']->get_frontend_language()=='german') { $txt.= ';background-color:#AAA;padding:5px;border-radius:5px;'; }; $txt.="' src='".level."inc/imgs/flags/Germany.png' alt='Deutsch' title='Deutsch'/></a>";
$myPage->add_content("<td>$txt</td>");
$page->change_parameter('change_language','english');
$txt = "<a href='".$page->get_link()."'><img style='"; if($_SESSION['login_user']->get_frontend_language()=='english') { $txt.= ';background-color:#AAA;padding:5px;border-radius:5px;'; }; $txt.="' src='".level."inc/imgs/flags/United Kingdom(Great Britain).png' alt='English' title='English'/></a>";
$myPage->add_content("<td>$txt</td>");
$myPage->add_content("</tr></table>");

$myPage->add_content("
</div>
</div>
</div>
</div>		
            "); 

            $myPage->add_content("
<script>
    CKEDITOR.replace('description');
    CKEDITOR.config.removePlugins = 'elementspath';
    CKEDITOR.config.resize_enabled = false;
</script>

");    


            $myPage->add_content("</div>");
        }
        else
        {
            $db->sql_query("SELECT * FROM courses WHERE course_user_id='".$_SESSION['login_user']->id."'");
            while($d = $db->get_next_res())
            {
                $myCourse = new course($d->course_id);
                $myPage->add_content($myCourse->get_card_edit());
            }

        }
	}
	else 
    {
		$myPage->add_content_with_translation('Kein Benutzer eingeloggt');
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
