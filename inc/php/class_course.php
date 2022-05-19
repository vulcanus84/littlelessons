<?php
//*****************************************************************************
//09.05.2022 Claude Hübscher
//-----------------------------------------------------------------------------
//*****************************************************************************

class course
{
    private $db;

    //Course
    public $id;
    public $title;
    public $description;
    public $price;
    public $teaser;
    public $teacher_id;
    public $min_attendees;
    public $max_attendees;

    //Appointment
    public $appointment_id;
    public $date;
    public $start_time;
    public $end_time;
    public $num_attendees;
    public $min_attendees_reached;


    function __construct($id=null)
    {
        include(level.'inc/db.php');
        $this->db = $db;
        if($id>0)
        {
            $this->load_by_course_id($id);
        }
    }

    function load_by_course_id($id)
    {
        $data = $this->db->sql_query_with_fetch("SELECT * FROM courses WHERE course_id=:id",array('id'=>$id));
        $this->id=$id;
        $this->title = $data->course_title;
        $this->description = $data->course_description;
        $this->text = $data->course_description;
        $this->teacher_id = $data->course_user_id;
        $this->min_attendees = $data->course_min_attendees;
        $this->max_attendees = $data->course_max_attendees;
    }

    function load_by_appointment_id($id)
    {
        $data = $this->db->sql_query_with_fetch("SELECT *,
                                                DATE_FORMAT(appointment_start,'%d.%m.%Y') as start_d,
                                                DATE_FORMAT(appointment_start,'%H:%i') as start_t,
                                                DATE_FORMAT(appointment_end,'%d.%m.%Y') as end_d,
                                                DATE_FORMAT(appointment_end,'%H:%i') as end_t
                                            FROM appointments 
                                            LEFT JOIN courses on appointments.appointment_course_id = courses.course_id
                                            LEFT JOIN 
                                            (
                                                SELECT MAX(appointment2user_appointment_id) as x, COUNT(*) as num_attendees FROM appointment2user GROUP BY appointment2user_appointment_id
                                            ) as count_apps ON appointments.appointment_id = count_apps.x
                                            WHERE appointment_id=:id",array('id'=>$id));
        $this->load_by_course_id($data->course_id);

        $this->appointment_id=$id;
        $this->date = $data->start_d;
        $this->start_time = $data->start_t;
        $this->end_time = $data->end_t;
        $this->num_attendees = $data->num_attendees;
        if($this->num_attendees+1<$this->min_attendees) { $this->min_attendees_reached = false; } else {$this->min_attendees_reached = true; }
    }

    function get_card()
    {
      $txt = "";
      $txt.= "<div class='col-sm-6 col-xl-4 my-2'>";
      $txt.= "  <div class='card'>";
      $txt.= "    <div class='card-body'>";
      $txt.= "      <h5 class='card-title'>".$this->title."</h5>";
      $txt.= "      <p class='card-text'>".$this->text."</p>";
      $txt.= "      <a href='course_details.php?id=".$this->id."' class='btn btn-primary'>Kurs anschauen</a>";
      $txt.= "    </div>";
      $txt.= "  </div>";
      $txt.= "</div>";
      return $txt;
    }

    function get_card_edit()
    {
      $txt = "";
      $txt.= "<div class='col-sm-6 col-xl-4 my-2'>";
      $txt.= "  <div class='card'>";
      $txt.= "    <div class='card-body'>";
      $txt.= "      <h5 class='card-title'>".$this->title."</h5>";
      $txt.= "      <a href='?course_id=".$this->id."' class='btn btn-warning'>Kurs bearbeiten</a>";
      $txt.= "    </div>";
      $txt.= "  </div>";
      $txt.= "</div>";
      return $txt;
    }

    function get_details()
    {
        $txt = "";
        $txt.= "<!-- Kurs buchen -->
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
        
                <div class='container-lg'>
                    <div class='row'>
                        <div class='col-12 my-2'>
                            <h1>".$this->title."</h1>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-6 col-xl-4 my-2'>
                            ".$this->get_course_pic()."
                        </div>
                        <div class='col-sm-6 col-xl-4 my-2'>
                            ".$this->text."
                        </div>
                        <div class='col-sm-12 col-xl-4 my-2'>
                            <h3>Termine</h3>
                            <div id='appointments'>
                            ".$this->get_appointments()."
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-4 my-2'>
                            ".$this->get_teacher_pic()."
                        </div>
                        <div class='col my-2 d-flex align-items-center'>
                            <h2 class='text-secondary'>\"Fotografieren ist die schönste Sache der Welt\"</h2>
                        </div>
                    </div>
                </div>";
        return $txt;
    }

    public function get_teacher_pic()
    {
        $myUser = new user($this->teacher_id);
        return "<h3>Kursleiter</h3><img class='img-fluid' style='max-height:200px;' src='".$myUser->get_pic_path()."'?id=".time()."' alt='".$myUser->fullname."' title='".$myUser->fullname."'/>";
    }

    public function get_course_pic()
    {
        if(file_exists("app_course_admin/course_pics/".$this->id.".jpg"))
        {
            return "<img class='img-fluid rounded-3' src='".level."app_course_admin/course_pics/".$this->id.".jpg' alt='".$this->title."' title='".$this->title."'/>";
        }
        if(file_exists("app_course_admin/course_pics/".$this->id.".png"))
        {
            return "<img class='img-fluid rounded-3' src='".level."app_course_admin/course_pics/".$this->id.".png' alt='".$this->title."' title='".$this->title."'/>";
        }
    }

    public function get_appointments()
    {
        $txt = "";
        $db2 = clone($this->db);
        $data = $db2->sql_query("SELECT *
                                FROM appointments 
                                WHERE appointment_course_id=:id",array('id'=>$this->id));
        while($d = $db2->get_next_res())
        {
            $this->load_by_appointment_id($d->appointment_id);
            $txt.= "<div class='my-2 p-2' style='border:2px solid gray;border-radius:10px;'>";
            $num_free = $this->max_attendees-$this->num_attendees;
            if($num_free > 1) { $txt_free_places = $num_free." Plätze frei"; }
            elseif ($num_free == 1) { $txt_free_places = "1 Platz frei"; }
            else { $txt_free_places = "Kurs ausgebucht"; }
            

            $txt.= "<table style='width:100%;'><tr><td><i class='bi bi-calendar-date me-2'></i>".$this->date."</td><td rowspan='3' style='text-align:right;'>";
            if($num_free > 0)
            {
                if($this->min_attendees_reached) { $class = 'btn-success'; } else { $class = 'btn-warning'; }
                $txt.= "
                <button id='appointment".$this->appointment_id."' type='button' class='btn ".$class."' data-bs-toggle='modal' data-bs-target='#exampleModal' onclick=\"load_modal(".$d->appointment_id.");\">
                    Kurs buchen <br/>(".$txt_free_places.")
                </button>";
            }
            else
            {
                $txt.= "
                <button id='appointment".$this->appointment_id."' type='button' class='btn btn-secondary'>
                    ".$txt_free_places."
                </button>";

            }
            $txt.= "</td></tr><tr><td><i class='bi bi-alarm me-2'></i>".$this->start_time." - ".$this->end_time."</td></tr>";
            $txt.= "</tr><tr><td><i class='bi bi-people-fill me-2'></i>".$this->min_attendees."-".$this->max_attendees."</td></tr>";
            
            $txt.= "</table>";
            $txt.= "</div>";
        }
        return $txt;
    }

    public function get_booking_confirmation()
    {
        if($this->min_attendees_reached)
        {
            $txt = "Mit dieser Bestätigung buchen sie den Kurs <b>".$this->title."</b> am ".$this->date." von ".$this->start_time." - ".$this->end_time." Uhr definitiv.<p/>
            <br/>Sie erhalten vom Kursleiter dann noch eine Bestätigung und ev. weitere Informationen<hr/>";
            $txt.= "<button type='button' class='btn btn-primary' onclick=\"book_course(".$this->appointment_id.");\">Kurs definitiv buchen</button>";
        }
        else
        {
            $txt = "Mit dieser Bestätigung buchen sie den Kurs <b>".$this->title."</b> am ".$this->date." von ".$this->start_time." - ".$this->end_time." Uhr definitiv.<p/>
            <br/>Aktuell sind noch zu wenige Teilnehmer angemeldet. Sie werden informiert, falls der Kurs abgesagt werden muss.<hr/>";
            $txt.= "<button type='button' class='btn btn-primary' onclick=\"book_course(".$this->appointment_id.");\">Kurs definitiv buchen</button>";
        }
        return $txt;
    }

}

?>