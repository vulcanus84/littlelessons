<?php

define("level","./");									//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
	$myPage = new page();
	$myPage->login_required=false;
	$myPage->set_title("Little Lessons");
    if(isset($_GET['id']))
    {
        $my_course = new course($_GET['id']);
        if(isset($_GET['action']) && $_GET['action']=='ajax')
        {
            die($my_course->get_appointments());
        }
        $myPage->add_js("

        function load_modal(appointment_id)
        {
            $.ajax({
                url : 'api.php',
                data: {'request_typ':'booking_confirmation','appointment_id':appointment_id},
                type: 'GET',
        
                success: function(data){
                    $('#modal_body').html(data);
                }
            });
        }
        function reload(div_id,arr_data)
        {
            $.ajax({
                url : 'course_details.php?action=ajax&id=".$_GET['id']."&div='+div_id,
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
            function book_course(appointment_id)
            {
                $.ajax({
                    url : 'api.php',
                    data: {'request_typ':'book_appointment','appointment_id':appointment_id,'user_id':".$_SESSION['login_user']->id."},
                    type: 'GET',
            
                    success: function(data){
                        reload('appointments');
                    }
                });
                $('#exampleModal').modal('hide');
            }
            ");
    
        }
        $myPage->add_content($my_course->get_details());
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