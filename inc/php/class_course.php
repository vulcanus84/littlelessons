<?php
//*****************************************************************************
//09.05.2022 Claude Hübscher
//-----------------------------------------------------------------------------
//*****************************************************************************
class course
{
    public $id;
    public $title;
    public $price;
    public $teaser;
    public $teacher_id;

    function __construct($id=null)
    {
        if($id>0)
        {
            $this->load($id);
        }
    }

    function load($id)
    {
        include(level.'inc/db.php');
        $data = $db->sql_query_with_fetch("SELECT * FROM courses WHERE course_id=:id",array('id'=>$id));
        $this->id=$id;
        $this->title = $data->course_title;
        $this->text = $data->course_description;
        $this->teacher_id = $data->course_user_id;
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
                    <div class='modal-body'>
                        <form id='book_course' action='book.php' method='POST' name='register'>
                        <div class='form-floating mb-3'>
                            <select class='form-select' id='gender' name='gender' required>
                            <option selected></option>
                            <option value='Herr'>Herr</option>
                            <option value='Frau'>Frau</option>
                            </select>
                            <label for='gender'>Anrede</label>
                        </div>
                        <div class='form-floating mb-3'>
                            <input type='hidden' id='appointment_id' name='appointment_id'/>
                            <input type='text' class='form-control form-control-lg' id='firstname' name='firstname' required>
                            <label for='firstname'>Vorname</label>
                        </div>
                        <div  class='form-floating mb-3'>
                            <input type='text' class='form-control' id='lastname' name='lastname' required>
                            <label for='lastname' class='form-label'>Nachname</label>
                        </div>
                        <div class='form-floating mb-3'>
                            <input type='email' class='form-control' id='email' name='email' required>
                            <label for='email' class='form-label'>E-Mail Adresse</label>
                        </div>
                        <button onclick='this.submit();' type='submit' class='btn btn-primary'>Registrieren</button>
                        </form>
            
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
                            ".$this->get_appointments()."
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
        return "<img class='img-fluid rounded-3' src='".level."app_course_admin/course_pics/".$this->id.".jpg' alt='".$this->title."' title='".$this->title."'/>";
    }

    public function get_appointments()
    {
        $txt = "<h3>Termine</h3>";
        include(level.'inc/db.php');
        $data = $db->sql_query("SELECT *, 
                                DATE_FORMAT(appointment_start,'%d.%m.%Y') as start_d,
                                DATE_FORMAT(appointment_start,'%H:%i') as start_t,
                                DATE_FORMAT(appointment_end,'%d.%m.%Y') as end_d,
                                DATE_FORMAT(appointment_end,'%H:%i') as end_t
                                FROM appointments WHERE appointment_course_id=:id",array('id'=>$this->id));
        while($d = $db->get_next_res())
        {
            $txt.= "<div class='my-2 p-2' style='border:2px solid gray;border-radius:10px;'>";
            $txt.= "<table style='width:100%;'><tr><td>".$d->start_d."</td><td rowspan='2' style='text-align:right;'>
            <button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#exampleModal' onclick=\"$('#appointment_id').val(".$d->appointment_id.");\">
            Kurs buchen   
          </button>
            </td></tr><tr><td>".$d->start_t." - ".$d->end_t."</td></tr></table>";
            $txt.= "</div>";
        }
        return $txt;
    }

}

?>