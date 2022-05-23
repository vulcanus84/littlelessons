<?php

define("level","../");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
	$myPage->login_required=false;
	$myPage->set_title("Little Lessons");
    if(isset($_GET['id']))
    {
        $myCourse = new course($_GET['id']);
        if(isset($_GET['action']) && $_GET['action']=='ajax')
        {
            die($myCourse->get_appointments());
        }
        $myPage->add_js("

        function load_modal(appointment_id)
        {
            $.ajax({
                url : '".level."api.php',
                data: {'request_typ':'booking_confirmation','appointment_id':appointment_id},
                type: 'GET',
        
                success: function(data){
                    $('#modal_body').html(data);
                }
            });
        }
        function load(div_id,arr_data)
        {
            arr_data_add = {'course_id':".$_GET['id'].",'div': div_id};
            arr_data = Object.assign(arr_data,arr_data_add);
            
            $.ajax({
                url : '".level."api.php',
                data: arr_data,
                type: 'GET',
        
                success: function(data){
                    $('#'+div_id).html(data);
                }
            });
        }");
        if(isset($_SESSION['login_user']))
        {
            $myPage->add_js("
            function book_appointment(appointment_id)
            {
                load('appointments',{'request_typ':'book_appointment','appointment_id':appointment_id,'user_id':".$_SESSION['login_user']->id."});
                load('appointments',{'request_typ':'appointments_of_course'});
                $('#exampleModal').modal('hide');
            }
            ");
    
        }
        $myPage->add_content("
        <div class='modal fade' id='exampleModal' tabindex='-1' aria-labelledby='exampleModalLabel' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='exampleModalLabel'>Kurs buchen</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Schliessen'></button>
            </div>
            <div class='modal-body' id='modal_body'>
            </div>
            </div>
        </div>
        </div>

            <div class='row'>
                <div class='col-12 my-2' id='title'>
                    <h1>".$myCourse->title."</h1>
                    <button class='btn btn-primary' onclick=\"reload('title',[]);\">Reload title</button>
                </div>
            </div>
            <div class='row'>
                <div class='col-sm-6 col-xl-4 my-2'>
                    ".$myCourse->get_course_pic()."
                </div>
                <div class='col-sm-6 col-xl-4 my-2'>
                    ".$myCourse->text."
                </div>
                <div class='col-sm-12 col-xl-4 my-2'>
                    <h3>Termine</h3>
                    <div id='appointments'>
                    ".$myCourse->get_appointments()."
                    </div>
                </div>
            </div>
            <div class='row'>
                <div class='col-4 my-2'>
                    ".$myCourse->get_teacher_pic()."
                </div>
                <div class='col my-2 d-flex align-items-center'>
                    <h2 class='text-secondary'>\"Fotografieren ist die schönste Sache der Welt\"</h2>
                </div>
        </div>");
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