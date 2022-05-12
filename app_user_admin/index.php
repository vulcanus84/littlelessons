<?php
define("level","../");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
if(!isset($_SESSION['login_user'])) { header("Location: ../index.php"); }
try
{
	$myPage = new page();
	$myTournament = new tournament($db);

	if(!isset($_GET['order_by'])) { $_GET['order_by']='location'; }

	if(isset($_GET['action']) && isset($_POST['user_account']))
	{
		$folder = 'user_pics/';
		$username = $_POST['user_account'];
		if(isset($_POST['user_id']))
		{
			$birthday = $_POST['user_birthday'];
			$user_id = $_POST['user_id'];
			if(isset($_POST['user_hide'])) { $user_hide = '1'; } else { $user_hide = '0'; }
			if($birthday=='')
			{
				$db->update(array('user_account'=>$_POST['user_account'],'user_firstname'=>$_POST['user_firstname'],'user_lastname'=>$_POST['user_lastname'],'user_gender'=>$_POST['user_gender'],'user_training_location'=>$_POST['user_training_location'],'user_birthday'=>null,'user_hide'=>$user_hide),'users','user_id',$user_id);
			}
			else
			{
				$birthday = $helper->date2iso($_POST['user_birthday']);
				$db->update(array('user_account'=>$_POST['user_account'],'user_firstname'=>$_POST['user_firstname'],'user_lastname'=>$_POST['user_lastname'],'user_gender'=>$_POST['user_gender'],'user_training_location'=>$_POST['user_training_location'],'user_birthday'=>$birthday,'user_hide'=>$user_hide),'users','user_id',$user_id);
			}
		}
		else
		{
			$db->insert(array('user_account'=>$_POST['user_account'],'user_gender'=>$_POST['user_gender'],'user_training_location'=>$_POST['user_training_location']),'users');
			$user_id = $db->last_inserted_id;
			$page->change_parameter('user_id',$user_id);
		}

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

				    $filename_new = 'uploads/'.microtime().$name;
				    rename($folder.$name, $filename_new);

						imagedestroy($image);
						imagedestroy($image_s);
						imagedestroy($mask);

		    }
		}
		$page->remove_parameter('action');
		$page->remove_parameter('ajax');

		$myUser = new user($user_id);
		$myUser->create_star_image();

		header("Location: ".$page->get_link());
	}

	$page->change_parameter('x','1');
	$_SERVER['link'] = $page->get_link();
	if(PLATFORM=='IPHONE') { $tmp = "$('#left_col').hide();"; } else { $tmp=''; }

	if(isset($_GET['user_id']))
	{
		$db->sql_query("SELECT user_id FROM users WHERE user_id='$_GET[user_id]'");
		if($db->count()>0)
		{
			$myPage->add_js("
				$( document ).ready(function() {
					$('#right_col').load('$_SERVER[link]&ajax=show_infos');
					".$tmp."
				});
			");
		}
		else
		{
			header("Location: ".level."app_user_admin/index.php");
		}
	}


	$myPage->add_js("
		$(window).load(function() {
		  if (sessionStorage.scrollTop != 'undefined') {
		    $('#left_col').scrollTop(sessionStorage.scrollTop);
		  }
		});

		function new_user()
		{
			".$tmp."
			$('#right_col').load('$_SERVER[link]&ajax=new_user');
		}

		function test(id)
		{
			$('#inpPicture').trigger('click');
		}

		function show_infos(user_id)
		{
			".$tmp."
			$('#right_col').load('$_SERVER[link]&ajax=show_infos&user_id='+user_id);
		}

		function show_history(user_id)
		{
			$('#right_col').load('$_SERVER[link]&ajax=show_history&user_id='+user_id);
		}


		function delete_permission(user_id)
		{
			$('#right_col').load('$_SERVER[link]&ajax=delete_permission&user_id='+user_id);
		}

		function delete_user(user_id)
		{
			var my_url = '$_SERVER[link]&ajax=delete_user&user_id=' + user_id;
			$.ajax({ url: my_url }).done(
			function(data)
			{
				location.reload();
			});
		}

		function delete_pic(user_id)
		{
			var my_url = '$_SERVER[link]&ajax=delete_pic&user_id=' + user_id;
			$.ajax(my_url).done(
			function(data)
			{
				$('#right_col').load('$_SERVER[link]&ajax=show_infos&user_id='+user_id);
				$('#left_col').load('$_SERVER[link]&ajax=show_left_col');
			});
		}
	");

	if(!IS_AJAX)
	{
		//Display page
		//$myPage->set_title("Badminton Academy");
		$myPage->permission_required=false;
		$myPage->set_title("Spielerverwaltung");

		if(PLATFORM=='IPHONE')
		{
			$myPage->add_content("<div id='menu'>");
			$myPage->add_content("	<div id='menu_left'>");
			$myPage->add_content("		<div class='menu_item'><button style='background-color:orange;' onclick='window.location=\"".level."app_tournaments/index.php\"'>Turniere</button></div>");
			$myPage->add_content("		<div class='menu_item''><button style='background-color:blue;border-left:5px solid lightblue;border-right:5px solid lightblue;' onclick='window.location=\"".level."app_user_admin/index.php\"'>Spieler</button></div>");
			$myPage->add_content("		<div class='menu_item'><button onclick='new_user();'>Neuer Spieler</button></div>");
			$myPage->add_content("	</div>");
			$myPage->add_content("</div>");
			$myPage->add_content("<div id='top_left_col'>");
			$myPage->add_content("<a href='".$page->change_parameter('order_by','alphabetical')."'><img style='height:48px;' src='".level."inc/imgs/sort_az_descending.png' title='Alphabetisch' alt='Alphabetisch' /></a>");
			$myPage->add_content("<a href='".$page->change_parameter('order_by','gender')."'><img style='height:48px;' src='".level."inc/imgs/male_female.png' title='Geschlecht' alt='Geschlecht' /></a>");
			$myPage->add_content("<a href='".$page->change_parameter('order_by','age')."'><img style='height:48px;' src='".level."inc/imgs/sort_by_age.png' title='Alter' alt='Alter' /></a>");
			$myPage->add_content("<a href='".$page->change_parameter('order_by','location')."'><img style='height:48px;' src='".level."inc/imgs/sort_by_location.png' title='Trainingsort' alt='Trainingsort' /></a>");
			if(isset($_GET['show_hidden']) && $_GET['show_hidden']=='1') { $val = '0'; } else { $val = '1'; }
			$page->reset();
			$myPage->add_content("<a href='".$page->change_parameter('show_hidden',$val)."'><img style='height:48px;border-left:1px solid black;' src='".level."inc/imgs/hidden.png' title='Versteckte einblenden' alt='Versteckte einblenden' /></a>");
			$myPage->add_content("<hr style='margin:0px;'>");
			$myPage->add_content("<div id='left_col' onscroll='sessionStorage.scrollTop = $(this).scrollTop();'>");
			$myPage->add_content($myTournament->get_all_users('show_infos',$_GET['order_by']));
			$myPage->add_content("</div>");
			$myPage->add_content("</div>");
			$myPage->add_content("<div id='right_col'>");
			$myPage->add_content("</div>");
		}
		else
		{
			$myPage->add_content("<div id='menu'>");
			$myPage->add_content("	<div id='menu_left'>");
			$myPage->add_content("		<div class='menu_item'><button style='background-color:orange;' onclick='window.location=\"".level."app_tournaments/index.php\"'>Turniere</button></div>");
			$myPage->add_content("		<div class='menu_item''><button style='background-color:blue;border-left:5px solid lightblue;border-right:5px solid lightblue;' onclick='window.location=\"".level."app_user_admin/index.php\"'>Spieler</button></div>");
			$myPage->add_content("	</div>");
			$myPage->add_content("	<div id='menu_right'>");
			$myPage->add_content("		<div class='menu_item'><button onclick='new_user();'>Neuer Spieler</button></div>");
			$myPage->add_content("	</div>");
			$myPage->add_content("</div>");
			$myPage->add_content("<div id='top_left_col'>");
			$myPage->add_content("<a href='".$page->change_parameter('order_by','alphabetical')."'><img style='width:4vw;' src='".level."inc/imgs/sort_az_descending.png' title='Alphabetisch' alt='Alphabetisch' /></a>");
			$myPage->add_content("<a href='".$page->change_parameter('order_by','gender')."'><img style='width:4vw;' src='".level."inc/imgs/male_female.png' title='Geschlecht' alt='Geschlecht' /></a>");
			$myPage->add_content("<a href='".$page->change_parameter('order_by','age')."'><img style='width:4vw;' src='".level."inc/imgs/sort_by_age.png' title='Alter' alt='Alter' /></a>");
			$myPage->add_content("<a href='".$page->change_parameter('order_by','location')."'><img style='width:4vw;' src='".level."inc/imgs/sort_by_location.png' title='Trainingsort' alt='Trainingsort' /></a>");
			if(isset($_GET['show_hidden']) && $_GET['show_hidden']=='1') { $val = '0'; } else { $val = '1'; }
			$page->reset();
			$myPage->add_content("<a href='".$page->change_parameter('show_hidden',$val)."'><img style='width:4vw;border-left:1px solid black;' src='".level."inc/imgs/hidden.png' title='Versteckte einblenden' alt='Versteckte einblenden' /></a>");
			$myPage->add_content("<hr style='margin:0px;'>");
			$myPage->add_content("<div id='left_col' onscroll='sessionStorage.scrollTop = $(this).scrollTop();'>");
			$myPage->add_content($myTournament->get_all_users('show_infos',$_GET['order_by']));
			$myPage->add_content("</div>");
			$myPage->add_content("</div>");
			$myPage->add_content("<div id='right_col'>");
			$myPage->add_content("</div>");
		}
		print $myPage->get_html_code();
	}
	else
	{
		//************************************************************************************
		//AJAX Handling
		//************************************************************************************
		if(isset($_GET['user_id'])) { $myUser = new user($_GET['user_id']); } else { $myUser = new user(); }
		if($_GET['ajax']=='new_user') { print $myUser->get_new_user(); }
		if($_GET['ajax']=='show_infos') { print $myUser->get_user_infos(); }
		if($_GET['ajax']=='show_history') { print $myUser->get_user_history(); }
		if($_GET['ajax']=='show_left_col') { print $myTournament->get_all_users('show_infos',$_GET['order_by']); }
		if($_GET['ajax']=='delete_user') { $db->delete('users','user_id',$user_id); }
		if($_GET['ajax']=='get_all_users') { print $myTournament->get_all_users('show_infos',$_GET['order_by']); }


		if($_GET['ajax']=='add_user')
		{
			$db->insert(array('group2user_group_id'=>$_GET['tournament_id'],'group2user_user_id'=>$_GET['user_id']),'group2user');
			print $db->last_inserted_id;
		}

		if($_GET['ajax']=='delete_permission')
		{
			$data = $db->sql_query_with_fetch("SELECT * FROM users WHERE user_id='$_GET[user_id]'");
			$db->sql_query("SELECT MAX(group_title) as group_title, MAX(DATE_FORMAT(group_created,'%d.%m.%Y')) as c_date FROM games
											LEFT JOIN groups on game_group_id = group_id
											WHERE game_player1_id = '$_GET[user_id]' OR game_player2_id='$_GET[user_id]'
											GROUP BY group_id");
			if($db->count()>0)
			{
				$x = "<h2>".$data->user_account." ist noch in folgenden Turnieren eingetragen und kann nicht gelöscht werden.</h2><p>";
				while($d = $db->get_next_res())
				{
					$x.= $d->c_date." / ".$d->group_title."</br>";
				}
				$x.= "<h2>Tipp: Sie können Spieler auch ausblenden</h2>";
			}
			else
			{
				$x = "<h1>Willst du ".$data->user_account." wirklich löschen?</h1>";
				$x.= "<button onclick='delete_user($_GET[user_id]);' style='background-color:red;'>Ja</button>";
				$x.= "<button onclick='window.location=\"$_SERVER[PHP_SELF]?user_id=$_GET[user_id]\"'>Nein</button>";
			}
			print $x;
		}

		if($_GET['ajax']=='delete_user' OR $_GET['ajax']=='delete_pic')
		{
			$pic_path = level."app_user_admin/user_pics/".$_GET['user_id'].".png";
			$pic_path_t = "user_pics/".$_GET['user_id']."_t.png";
			$pic_path_star = "user_pics/".$_GET['user_id']."_stars.png";
			$pic_path_star_t = "user_pics/".$_GET['user_id']."_stars_t.png";
			if(file_exists($pic_path)) { unlink($pic_path); }
			if(file_exists($pic_path_t)) { unlink($pic_path_t); }
			if(file_exists($pic_path_star)) { unlink($pic_path_star); }
			if(file_exists($pic_path_star_t)) { unlink($pic_path_star_t); }
			if($_GET['ajax']=='delete_user')
			{
				$db->sql_query("DELETE FROM users WHERE user_id='$_GET[user_id]'");
			}
		}

		//************************************************************************************
	}
}
catch (Exception $e)
{
	$myPage = new page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}

//************************************************************************************
//own PHP Functions
//************************************************************************************



?>
