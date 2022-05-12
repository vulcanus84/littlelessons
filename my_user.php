<?php

define("level","./");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
	$myPage->permission_required=false;

	if(isset($_SESSION['login_user']))
	{
		$message = ""; $error = "";
		if(isset($_GET['action']) && $_GET['action']=='save_personals')
		{
			$_SESSION['login_user']->set_firstname($_POST['firstname']);
			$_SESSION['login_user']->set_lastname($_POST['lastname']);
			$_SESSION['login_user']->set_gender($_POST['gender']);
			$_SESSION['login_user']->set_email($_POST['email']);
			$_SESSION['login_user']->save();
			header('Location: '.$page->filename);
		}


		if(isset($_GET['action']) && $_GET['action']=='delete_pic')
		{
			$pic_path = level."app_user_admin/user_pics/".$_SESSION['login_user']->id.".png";
			$pic_path_t = level."app_user_admin/user_pics/".$_SESSION['login_user']->id."_t.png";
			if(file_exists($pic_path)) { unlink($pic_path); }
			if(file_exists($pic_path_t)) { unlink($pic_path_t); }
			header('Location: '.$page->filename);
		}

		if(isset($_GET['action']) && $_GET['action']=='change_pic')
		{
			$folder = 'app_user_admin/user_pics/';
			$user_id = $_SESSION['login_user']->id;
			foreach ($_FILES["pictures"]["error"] as $key => $error) {
			    if ($error == UPLOAD_ERR_OK) {
					//Add user to DB
					if(file_exists($folder.$user_id.'.png')) { unlink($folder.$user_id.'.png'); }
			        $tmp_name = $_FILES["pictures"]["tmp_name"][$key];
			        // basename() kann Directory Traversal Angriffe verhindern; weitere
			        // Gültigkeitsprüfung/Bereinigung des Dateinamens kann angebracht sein
			        $name = basename($_FILES["pictures"]["name"][$key]);
			        move_uploaded_file($tmp_name, $folder.$name);
	
							//Crop image to 1:1 ratio
							$filename = $folder.$name;
							$im = imagecreatefromstring(file_get_contents($filename));
	
							$w = imagesx($im);
							$h = imagesy($im);
	
							$size = min($w,$h);
	
							if($w>$h) { $diff_x = ($w-$h)/2; } else { $diff_x = 0; }
							if($w<$h) { $diff_y = ($h-$w)/2; } else { $diff_y = 0; }
	
							$image_s = imagecrop($im, ['x' => $diff_x, 'y' => $diff_y, 'width' => $size, 'height' => $size]);
							imagedestroy($im);
	
							//Round mask
							$width = imagesx($image_s);
							$height = imagesy($image_s);
	
							$newwidth = 500;
							$newheight = 500;
	
							$image = imagecreatetruecolor($newwidth, $newheight);
							imagealphablending($image, true);
							imagecopyresampled($image, $image_s, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
	
							//create masking
							$mask = imagecreatetruecolor($newwidth, $newheight);
	
							$transparent = imagecolorallocate($mask, 255, 0, 0);
							imagecolortransparent($mask,$transparent);
	
							imagefilledellipse($mask, $newwidth/2, $newheight/2, $newwidth, $newheight, $transparent);
	
							$red = imagecolorallocate($mask, 0, 0, 0);
							imagecopymerge($image, $mask, 0, 0, 0, 0, $newwidth, $newheight, 100);
							imagecolortransparent($image,$red);
							imagefill($image, 0, 0, $red);
	
							$exif = exif_read_data($filename);
	
							if (isset($exif['Orientation']))
							{
							  switch ($exif['Orientation'])
							  {
							    case 3:
							      // Need to rotate 180 deg
										$image = imagerotate($image, 180, 0);
							      break;
	
							    case 6:
							      // Need to rotate 90 deg clockwise
										$image = imagerotate($image, -90, 0);
							      break;
	
							    case 8:
							      // Need to rotate 90 deg counter clockwise
										$image = imagerotate($image, 90, 0);
							      break;
							  }
							}
	
							//output, save and free memory
							imagepng($image,$folder.$user_id.'.png');
	
							//*********************************
							//Create Thumbnail
							//*********************************
	
							//Round mask
							$width = imagesx($image_s);
							$height = imagesy($image_s);
	
							$newwidth = 120;
							$newheight = 120;
	
							$image = imagecreatetruecolor($newwidth, $newheight);
							imagealphablending($image, true);
							imagecopyresampled($image, $image_s, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
	
							//create masking
							$mask = imagecreatetruecolor($newwidth, $newheight);
	
							$transparent = imagecolorallocate($mask, 255, 0, 0);
							imagecolortransparent($mask,$transparent);
	
							imagefilledellipse($mask, $newwidth/2, $newheight/2, $newwidth, $newheight, $transparent);
	
							$red = imagecolorallocate($mask, 0, 0, 0);
							imagecopymerge($image, $mask, 0, 0, 0, 0, $newwidth, $newheight, 100);
							imagecolortransparent($image,$red);
							imagefill($image, 0, 0, $red);
	
							$exif = exif_read_data($filename);
	
							if (isset($exif['Orientation']))
							{
							  switch ($exif['Orientation'])
							  {
							    case 3:
							      // Need to rotate 180 deg
										$image = imagerotate($image, 180, 0);
							      break;
	
							    case 6:
							      // Need to rotate 90 deg clockwise
										$image = imagerotate($image, -90, 0);
							      break;
	
							    case 8:
							      // Need to rotate 90 deg counter clockwise
										$image = imagerotate($image, 90, 0);
							      break;
							  }
							}
	
							//output, save and free memory
							imagepng($image,$folder.$user_id.'_t.png');
	
					    $filename_new = 'app_user_admin/uploads/'.microtime().$name;
					    rename($folder.$name, $filename_new);
	
							imagedestroy($image);
							imagedestroy($image_s);
							imagedestroy($mask);
	
			    }
			}
			$page->remove_parameter('action');
			$page->remove_parameter('ajax');
	
			header("Location: ".$page->get_link());
		}


		if(isset($_GET['action']) && $_GET['action']=='change_password')
		{
			if(trim($_POST['new_password'])!='')
			{
				if($_POST['new_password']==$_POST['new_password_repeat'])
				{
					try
					{
						$_SESSION['login_user']->update_password($_POST['old_password'],$_POST['new_password_repeat']);
						$message = "Neues Passwort gespeichert";
					} catch (Exception $e){ $error = $e->getMessage(); }
				}
				else {
					$error = "Neue Passwörter stimmen nicht überein";
				}
			}
			else {
				$error = "Neues Passwort darf nicht leer sein";
			}
		}
		
		$myPage->add_js("
	
			function upload_pic(id)
			{
				$('#inpPicture').trigger('click');
			}
			");

			
		if(!isset($_POST['old_password'])) { $_POST['old_password'] = ""; }
		$myPage->add_content("<div class='container-lg'>");
		$myPage->add_content_with_translation("<h3 class='my-3'>Meine Angaben</h3>");
		if($message!='') { $myPage->add_content($myPage->show_info($myPage->t->translate($message))); }
		if($error!='') { $myPage->add_content($myPage->show_error($myPage->t->translate($error))); }
		$myPage->add_content("
								<div class='accordion mt-3' id='accordionExample'>
								<div class='accordion-item'>
								<h2 class='accordion-header' id='headingFour'>
									<button class='accordion-button' type='button' data-bs-toggle='collapse' data-bs-target='#collapseFour' aria-expanded='false' aria-controls='collapseFour'>
								");
			$myPage->add_content_with_translation("Personalien");
			$myPage->add_content("
										</button>
									</h2>
									<div id='collapseFour' class='accordion-collapse collapse show' aria-labelledby='headingFour' data-bs-parent='#accordionExample'>
										<div class='accordion-body'>
										");
			$myPage->add_content("<form id='personals' action='".$page->change_parameter('action','save_personals')."' method='POST' >");
	
			$myPage->add_content("      <div class='form-floating mb-3'>");
			$myPage->add_content("        <select class='form-select' id='gender' name='gender' required>");
			$myPage->add_content("          <option selected></option>");
			$myPage->add_content("          <option value='Frau' "); if($_SESSION['login_user']->gender=='Frau') { $myPage->add_content(" selected"); } $myPage->add_content(">Frau</option>");
			$myPage->add_content("          <option value='Herr' "); if($_SESSION['login_user']->gender=='Herr') { $myPage->add_content(" selected"); } $myPage->add_content(">Herr</option>");
			$myPage->add_content("        </select>");
			$myPage->add_content("        <label for='gender'>Anrede</label>");
			$myPage->add_content("      </div>");
			$myPage->add_content("      <div class='form-floating mb-3'>");
			$myPage->add_content("        <input type='text' class='form-control' id='firstname' name='firstname' value='".$_SESSION['login_user']->firstname."' required>");
			$myPage->add_content("        <label for='firstname'>Vorname</label>");
			$myPage->add_content("      </div>");
			$myPage->add_content("      <div  class='form-floating mb-3'>");
			$myPage->add_content("        <input type='text' class='form-control' id='lastname' name='lastname' value='".$_SESSION['login_user']->lastname."' required>");
			$myPage->add_content("        <label for='lastname' class='form-label'>Nachname</label>");
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
		
		$myPage->add_content("<div class='container-lg'>");

	}
	else {
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
