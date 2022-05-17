<?php
    define("level","./");									//define the structur to to root directory (e.g. "../", for files in root set "")
    require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

    switch($_GET['request_typ'])
    {
        //Returns all appointments of course 
        //Parameters: course_id
        case 'appointments_of_course':
            $my_course = new course($_GET['course_id']);
            print $my_course->get_appointments();
            break;

        //Show booking confirmation
        //Parameters: course_id, user_id
        case 'booking_confirmation':
            if(!isset($_GET['appointment_id'])) { print "Kein Kurs angegeben"; }
            elseif(!isset($_SESSION['login_user'])) { print "Bitte zuerst einloggen"; }
            else
            {
                $my_course = new course();
                $my_course->load_by_appointment_id($_GET['appointment_id']);
                print $my_course->get_booking_confirmation();
            }
            break;

        case 'book_appointment':
            $db->insert(array('appointment2user_user_id'=>$_GET['user_id'],
                                'appointment2user_appointment_id'=>$_GET['appointment_id']
        )       ,'appointment2user');
            break;

        default :
            print "Request typ <b>".$_GET['request_typ']. "</b> not found";

    }
?>