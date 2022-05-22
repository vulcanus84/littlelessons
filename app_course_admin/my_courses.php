<?php

define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
	$myPage->permission_required=false;
    $myPage->add_js_link(level."inc/ckeditor/ckeditor.js");

	if(isset($_SESSION['login_user']))
	{
		$message = ""; $error = "";
        $myPage->add_js("
        function load_modal(course_id,typ)
        {
            $.ajax({
                url : '".level."api.php',
                data: {'request_typ':typ,'course_id':course_id},
                type: 'GET',
        
                success: function(data){
                    $('#modal_body').html(data);
                }
            });
        }

        function set_course_status(course_id,status)
        {
            $.ajax({
                url : '".level."api.php',
                data: {'request_typ':'set_course_status','course_id':course_id,'status':status},
                type: 'GET',
        
                success: function(data){
                    location.reload();
                }
            });
        }
        ");

		if($message!='') { $myPage->add_content($myPage->show_info($myPage->t->translate($message))); }
		if($error!='') { $myPage->add_content($myPage->show_error($myPage->t->translate($error))); }
		if(isset($_GET['course_id']))
        {
            $myPage->add_content("
                                    <div class='modal fade' id='exampleModal' tabindex='-1' aria-labelledby='exampleModalLabel' aria-hidden='true'>
                                    <div class='modal-dialog'>
                                        <div class='modal-content'>
                                        <div class='modal-header'>
                                            <h5 class='modal-title' id='exampleModalLabel'>Hinweis</h5>
                                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Schliessen'></button>
                                        </div>
                                        <div class='modal-body' id='modal_body'>
                                        </div>
                                        </div>
                                    </div>
                                    </div>
                                    ");
            //Add new course
            if($_GET['course_id']==0)
            {
                $myCourse = new course();
                $myCourse->save();    

            }
            $myCourse = new course($_GET['course_id']);
            $myPage->add_content("<div class='container-lg'><div class='row'><a href='my_courses.php' class='btn btn-primary mb-1'>Zurück</a></div></div>");
            $myPage->add_content("<h3 class='my-3'>".$myCourse->title."</h3>");
            $myPage->add_content("<div class='container-lg'>
            <div class='row my-3'>");
            if($myCourse->status=='Edit')
            {
                $myPage->add_content("<div class='col-4 text-center py-2 bg-warning rounded-pill'>Bearbeitung</div>");
                $myPage->add_content("<button data-bs-toggle='modal' data-bs-target='#exampleModal' onclick=\"load_modal(".$myCourse->id.",'approval_confirmation');\" class='btn col-4 text-center py-2 bg-light rounded-pill'>Prüfung</button>");
                $myPage->add_content("<button data-bs-toggle='modal' data-bs-target='#exampleModal' onclick=\"load_modal(".$myCourse->id.",'abort_confirmation');\" class='btn col-4 text-center py-2 bg-danger rounded-pill'>Abbrechen</button>");
            }
            if($myCourse->status=='Approval')
            {
                $myPage->add_content("<div class='col-4 text-center py-2 bg-light rounded-pill'>Bearbeitung</div>");
                $myPage->add_content("<div class='col-4 text-center py-2 bg-warning rounded-pill'>Prüfung</div>");
                $myPage->add_content("<div class='col-4 text-center py-2 bg-light rounded-pill'>Freigabe</div>");
            }
            if($myCourse->status=='Released')
            {
                $myPage->add_content("<button onclick=\"set_course_status('".$myCourse->id."','Edit');\" class='btn col-4 text-center py-2 bg-light rounded-pill'>Bearbeiten</button>");
                $myPage->add_content("<div class='col-4 text-center py-2 bg-light rounded-pill'>Prüfung</div>");
                $myPage->add_content("<div class='col-4 text-center py-2 bg-success rounded-pill'>Freigabe</div>");
            }


            $myPage->add_content("
            </div>
            </div>");
            if($myCourse->status=='Released')
            {
                $myPage->add_content("<p>Der Kurs ist aktuell nicht in Bearbeitung. Klicken sie auf bearbeiten um den Kurs zu editieren</p>");
            }

            if($myCourse->status=='Approval')
            {
                $myPage->add_content("Der Kurs wird aktuell geprüft und sie können keine Änderungen vornehmen.");
            }


            if($myCourse->status=='Edit')
            {
                $myPage->add_content("      <form id='personals' action='".$page->change_parameter('action','save_personals')."' method='POST' >");
                $myPage->add_content("          <div class='form-floating mb-3'>");
                $myPage->add_content("          <input type='text' class='form-control' id='title' name='title' value='".$myCourse->title."' required>");
                $myPage->add_content("          <label for='title'>Titel</label>");
                $myPage->add_content("          </div>");
                $myPage->add_content("          <div  class='form-floating mb-3'>");
                $myPage->add_content("              <textarea class='form-control' id='description' name='description' style='height:300px;' required>".$myCourse->description."</textarea>");
                $myPage->add_content("          </div>");
                $myPage->add_content_with_translation("<button onclick='this.submit();' class='btn btn-primary'>Speichern</button></td>");
                $myPage->add_content("      </form>");
                
    
                $myPage->add_content("<form id='new_user' action='".$page->change_parameter('action','change_pic')."' method='post' enctype='multipart/form-data'>");
                $myPage->add_content("<input type='hidden' id='user_id' name='user_id' value='".$_SESSION['login_user']->id."' />");
                $myPage->add_content($_SESSION['login_user']->get_picture(null,'upload_pic','150px',false));
                $myPage->add_content("<input style='visibility:hidden;' onchange='$(\"#new_user\").submit();' name='pictures[]' id='inpPicture' type='file' accept='image/*'/>");
                $myPage->add_content("</form>");
                $myPage->add_content_with_translation("<a class='btn btn-primary mt-3' onclick='upload_pic();' role='button'>Bild wechseln</a>");
                $myPage->add_content_with_translation("<a class='btn btn-warning mt-3' href='?action=delete_pic' role='button'>Bild löschen</a>");
    
                $myPage->add_content("
                
                                        <script>
                                            CKEDITOR.replace('description');
                                            CKEDITOR.config.removePlugins = 'elementspath';
                                            CKEDITOR.config.resize_enabled = false;
                                        </script>
                                        ");    
            }



            $myPage->add_content("</div>");
        }
        else
        {
            $myPage->add_content_with_translation("<h3 class='my-3'>Meine Kurse</h3>");
            $myPage->add_content("<div class='row justify-content-start'>");
            $db->sql_query("SELECT * FROM courses WHERE course_user_id='".$_SESSION['login_user']->id."'");
            while($d = $db->get_next_res())
            {
                $myCourse = new course($d->course_id);
                $myPage->add_content($myCourse->get_card_edit());
            }
            $myCourse = new course();
            $myPage->add_content($myCourse->get_card_new());
            $myPage->add_content("</div>");

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
