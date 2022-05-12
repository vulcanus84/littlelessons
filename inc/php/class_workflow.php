<?php
/**
 *This class manage the workflow functions in CCS
 *
 *EXAMPLE:
 *
 *$myWorkflow = new workflow($db);
 *$myWorkflow->load_existing('1000444');
 *print $myWorkflow->get_simple_text();
 ******************************************************************************
 */
include_once("class_mail_ccs.php");

class workflow
{
  public $db;
	private $html_code="";
	private $is_multipart=false;

	public $t;
	/**
	 *Name of the SQL Table
	*/
	public $sql_table;

	/**
	 *Filename of the form
	*/
	public $form_file_name;

	/**
	 *Only the name of the folder, which is the form located
	*/
	public $form_folder;

	/**
	 *Title of the workflow
	 */
	public $title;

	/**
	 *Possibility to define own SQL Statement to change user selection
	 */
	public $sql_user_selection;

	public $subtitle;

	public $user;
	public $request_user;
	public $step_user;

	public $workflow_typ_id;
	public $status;
	public $execution_date_field;
	public $mut_id;

	public $step_id;
	public $step_var;
	public $step_status;
	public $step_action;
	public $step_nr=0;
	public $step_source;
	public $step_condition;
	public $step_description;
	public $step_reminder;

	public $myMail;
	public $myLog;

  public function __construct($db,$typ_id=null,$wf_id=null,$step_id=null)
  {
    $this->db = clone($db);
		$this->myLog = new log(clone($db));
		$this->html_code.= "<table width='100%' border='0'><tr>";
		if(isset($_SESSION['login_user'])) { $this->t = new translation(clone($this->db),$_SESSION['login_user']->get_frontend_language()); } else { $this->t = new translation(clone($this->db)); }
		if(isset($step_id)) { $this->load_step($step_id); }
		elseif(isset($wf_id)) { $this->load_existing($wf_id); }
		elseif(isset($typ_id)) { $this->load_new($typ_id); }
  }

	//Create a new workflow from typ id
	public function load_new($typ_id)
	{
		$db = clone($this->db);
		$data = $db->sql_query_with_fetch("SELECT * FROM ccs_mut_typ WHERE mut_typ_id='$typ_id'");
		$this->workflow_typ_id = $typ_id;
		$this->form_folder = $data['mut_typ_datei'];
		$this->form_file_name = $data['mut_typ_datei']."/workflow_form.php";
		$this->sql_table = $data['mut_typ_tabelle'];
		$this->execution_date_field = $data['mut_typ_execution_date_field'];
		$this->title = $data['mut_typ_beschreibung'];
		$this->request_user = new user($db,$_SESSION['login_user']->id);
    $this->user = new user($db);
	}

	//Loads a created workflow
	public function load_existing($mut_id)
	{
		$db = clone($this->db);
		$data = $db->sql_query_with_fetch("SELECT * FROM ccs_mutationen WHERE mut_id='$mut_id'");
		$this->load_new($data['mut_typ']);
		$this->mut_id = $mut_id;
		$this->request_user = new user($db,$data['mut_startuser_id']);
		$this->user = new user($db,$data['mut_user_id']);
		$this->status = $data['mut_status'];
	}

	//Loads a created workflow and the current step
	public function load_step($step_id)
	{
		$db = clone($this->db);
		$data = $db->sql_query_with_fetch("SELECT * FROM ccs_mut_infos WHERE mut_infos_id='$step_id'");
		if(!$this->mut_id) { $this->load_existing($data['mut_infos_nr']); }
		$this->step_user = new user($db,$this->get_step_uid($step_id));
		$this->step_id = $step_id;
		$this->step_var = $data['mut_infos_var'];
		$this->step_text = $data['mut_infos_text'];
		$this->step_status = $data['mut_infos_status'];
		$this->step_action = $data['mut_infos_aktion'];
		$this->step_nr = $data['mut_infos_schritt'];

		$this->step_condition = $data['mut_infos_bedingung'];
		$this->step_reminder = $data['mut_infos_reminder'];
		$this->step_description = $data['mut_infos_beschreibung'];
		$this->step_source = $data['mut_infos_herkunft'];

	}

	public function send_mail($typ,$user_id,$step_id=null)
	{
		$db = clone($this->db);
		if($step_id!=null) { $data = $db->sql_query_with_fetch("SELECT * FROM ccs_mut_infos WHERE mut_infos_id='$step_id'"); }

		//Set translation to User which receives the email
		$myUser = new user(clone($db),$user_id);
		$this->t->set_language($myUser->get_frontend_language());

		$this->myMail = new ccs_mailer($db,$myUser->get_frontend_language());
		$this->myMail->add_recipient($user_id);

		switch(strtoupper($typ))
		{
			case 'OTHER_USER':
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Information')." (".$this->mut_id.")");
				$this->myMail->add_text($this->request_user->fullname." ".$this->t->translate('hat für sie folgenden Antrag gestartet').".<p/>".$this->get_text(true));
				break;

			case "ABSCHLUSS":
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Antrag erfolgreich abgeschlossen')." (".$this->mut_id.")");
				$this->myMail->add_text($this->t->translate('ihr Auftrag mit der Nummer')." ".$this->mut_id." ".$this->t->translate('wurde erfolgreich abgeschlossen')."<p/><hr/>".$this->get_user_info(true)."<hr/>".$this->get_text(true));
		    break;

	    case "ABGELEHNT":
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Antrag abgelehnt')." (".$this->mut_id.")");
				$this->myMail->add_text($this->t->translate('ihr Auftrag mit der Nummer')." ".$this->mut_id." ".$this->t->translate('wurde leider von')." ".$data['mut_infos_person']." ".$this->t->translate('abgelehnt')."<p/>".
																	$this->t->translate('Die Begründung lautet').":<p/><b>".nl2br($data['mut_infos_begruendung'])."</b><p/><hr/>".$this->get_user_info(true)."<hr/>".$this->get_text(true));
  	    break;

    	case "INFO":
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Information')." (".$this->mut_id.")");
				$this->myMail->add_text("<hr/>".$this->get_user_info(true)."<hr/>".$this->get_text(true)."<hr/>".$this->get_step_text(true));
	      break;

	    case "AUFTRAG":
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Antrag auszuführen')." (".$this->mut_id.")");
				$this->myMail->add_text("<hr/>".$this->get_user_info(true)."<hr/>".$this->get_text(true)."<hr/>");
				$x = $this->get_step_text(true);
				if(trim($x)!='') { $this->myMail->add_text($x."<hr/>"); }
				$this->myMail->add_text($this->t->translate('Mit dem folgenden Link gelangen Sie direkt zur Auftragsbestätigung')."<br><a href='".CCS_PATH."app_workflows/workflows/workflow.php?step_id=".$data['mut_infos_id']."'>".$this->t->translate('Link zur Auftragsbestätigung')."</a>");
	      break;

	    case "ERWEITERUNG MIT AUFTRAG":
	    case "AUFTRAG (FREIW. ERW.)":
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Auftrag und Ergänzung')." (".$this->mut_id.")");
				$this->myMail->add_text("<hr/>".$this->get_user_info(true)."<hr/>".$this->get_text(true)."<hr/>");
				$x = $this->get_step_text(true);
				if(trim($x)!='') { $this->myMail->add_text($x."<hr/>"); }
				$this->myMail->add_text($this->t->translate('Bitte benutzen Sie folgenden Link, um ihre Daten zu ergänzen und die Ausführung zu bestätigen')."<br><a href='".CCS_PATH."app_workflows/workflows/workflow.php?step_id=".$data['mut_infos_id']."'>".$this->t->translate('Link zur Auftragsbestätigung')."</a>");
	      break;

	    case "FREIGABE":
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Freigabe')." (".$this->mut_id.")");
				$this->myMail->add_text("<hr/>".$this->get_user_info(true)."<hr/>".$this->get_text(true)."<hr/>");
				$x = $this->get_step_text(true);
				if(trim($x)!='') { $this->myMail->add_text($x."<hr/>"); }
				$this->myMail->add_text($this->t->translate('Bitte benutzen Sie folgenden Link um den Antrag anzunehmen oder abzulehnen')."<br><a href='".CCS_PATH."app_workflows/workflows/workflow.php?step_id=".$data['mut_infos_id']."'>".$this->t->translate('Link zur Freigabe')."</a>");
	      break;

	    case "ERWEITERUNG MIT FREIGABE":
	    case "FREIGABE (FREIW. ERW.)":
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Freigabe und Ergänzung')." (".$this->mut_id.")");
				$this->myMail->add_text("<hr/>".$this->get_user_info(true)."<hr/>".$this->get_text(true)."<hr/>");
				$x = $this->get_step_text(true);
				if(trim($x)!='') { $this->myMail->add_text($x."<hr/>"); }
				$this->myMail->add_text($this->t->translate('Bitte benutzen Sie folgenden Link, um ihre Daten zu ergänzen und den Antrag zu bewilligen oder abzulehnen')."<br><a href='".CCS_PATH."app_workflows/workflows/workflow.php?step_id=".$data['mut_infos_id']."'>".$this->t->translate('Link zur Auftragsbestätigung')."</a>");
	      break;

	    case "WELCOME":
				$this->myMail->set_title($this->t->translate('Herzlich Willkommen im CentralChangeSystem'));
				$this->myMail->add_text($this->t->translate('welcome_text_part1')."<br><a href='".CCS_PATH."'>".CCS_PATH."</a><p/>".$this->t->translate('welcome_text_part2'));
	      break;

	    case "STORNIERUNG":
        $infos = $db->sql_query_with_fetch("SELECT * FROM ccs_mut_infos WHERE mut_infos_nr='$this->mut_id' AND mut_infos_schritt='99'");
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Stornierung')." (".$this->mut_id.")");
				$this->myMail->add_text($this->t->translate('der Auftrag mit der Nummer')." ".$this->mut_id." ".$this->t->translate('wurde von')." ".$infos['mut_infos_person']." ".$this->t->translate('mit folgender Begründung storniert')."<p/><b>".nl2br($infos['mut_infos_begruendung'])."</b><p/><hr/>".$this->get_user_info(true)."<hr/>".$this->get_text(true));
		    break;

	    case "UEBERGEBEN":
  	    $infos = $db->sql_query_with_fetch("SELECT * FROM ccs_mut_infos WHERE mut_infos_id='$data[mut_infos_uebergeben_von]'");
				$this->myMail->set_title($this->title." >> ".$this->t->translate('Übergabe')." (".$this->mut_id.")");
				$this->myMail->add_text($this->t->translate('Folgender Auftrag wurde ihnen von')." ".$infos['mut_infos_person']." ".$this->t->translate('mit folgender Begründung übergeben').":<p/><b>".nl2br($infos['mut_infos_begruendung'])."</b><p/><hr/>".$this->get_user_info(true)."<hr/>".$this->get_text(true)."<hr/>");
				$x = $this->get_step_text(true);
				if(trim($x)!='') { $this->myMail->add_text($x."<hr/>"); }
				$this->myMail->add_text($this->t->translate('<p/>Bitte benutzen Sie folgenden Link, um ihre Daten zu ergänzen und den Antrag zu bewilligen oder abzulehnen')."<br><a href='".CCS_PATH."app_workflows/workflows/workflow.php?step_id=".$data['mut_infos_id']."'>".$this->t->translate('Link zum Auftrag im CCS')."</a>");
	      break;

			default:
				throw new exception("Selected Mail-Typ not found (".$typ.")");
		}

		//Special settings for Servicedesk
		if($user_id=='590')
		{
// 			if(trim($this->user->email)!='')
// 			{
				$this->myMail->set_from($this->user->fullname,$this->user->email);
// 			}
			$this->myMail->set_title("CCS: ".$this->myMail->get_title());
		}

	  //if it's a reminder, Change subject
    if($step_id!=null)
		{
	  	$is_rem = $db->sql_query_with_fetch("SELECT * FROM ccs_mut_infos WHERE mut_infos_id='".$this->step_id."' AND mut_infos_status!='Abgeschlossen'");
	  	if($is_rem['mut_infos_last_remind'] != NULL) { $this->myMail->set_title($this->t->translate("REMINDER").": ".$this->myMail->get_title()); }
		}


		//Reset language to login user
		if(isset($_SESSION['login_user'])) { $this->t->set_language($_SESSION['login_user']->get_frontend_language()); }

		try
		{
	    $this->myMail->send_ccs_mail();
		}
	  catch (Exception $e)
	  {
			return $e->getMessage();
		}
	}

  //Load Workflows from database and display it in the bubble
  public function list_workflows($kat='')
  {
		if($kat=='')
		{
	    $this->db->sql_query("SELECT * FROM ccs_mut_typ WHERE mut_typ_aktiv='1'
														LEFT JOIN ccs_permissions_special ON mut_typ_permission = permissions_special_id
														ORDER BY mut_typ_sort_id, mut_typ_beschreibung");
		}
		else
		{
	    $this->db->sql_query("SELECT * FROM ccs_mut_typ
														LEFT JOIN ccs_permissions_special ON mut_typ_permission = permissions_special_id
														WHERE mut_typ_kategorie='$kat' AND mut_typ_aktiv='1' ORDER BY mut_typ_sort_id, mut_typ_beschreibung");
		}
    $txt = "";
    while($d = $this->db->get_next_res())
    {
			if($d['mut_typ_permission']=='' OR $_SESSION['login_user']->check_permission($d['permissions_special_name']))
			{
    	  $txt.= "<p class='flows'><a class='flows' href='app_workflows/workflows/workflow.php?id=$d[mut_typ_id]'>".$this->t->translate($d['mut_typ_beschreibung'])."</a></p>";
			}
    }
    return $txt;
  }

	public function create()
	{
		$data_requester = $this->db->sql_query_with_fetch("SELECT * FROM users WHERE user_id='".$this->request_user->id."'");
		$data_user = $this->db->sql_query_with_fetch("SELECT * FROM users WHERE user_id='$_GET[user_id]'");

    //manage single quotes
    foreach ($data_user as $eingabe => $val ) { if(!is_object($data_user[$eingabe])) { $data_user[$eingabe] = str_replace("'","&#39;",$val); } }

		$this->db->sql_query("INSERT INTO ccs_mutationen
                    (mut_typ, mut_status,mut_user,mut_user_id,
                     mut_startuser, mut_startuser_id,
                     mut_startdatum)
                  VALUES ('$this->workflow_typ_id','Unbestätigt','$data_user[user_vorname] $data_user[user_nachname]','$_GET[user_id]','$data_requester[user_vorname] $data_requester[user_nachname]','".$this->request_user->id."',GETDATE())");
		$mut_id = $this->db->last_inserted_id;

		$this->db->sql_query("UPDATE $this->sql_table SET mut_id='$mut_id' WHERE id='$_GET[mut_data_id]'");

    //copy all steps into working table
    $this->db->sql_query("SELECT * FROM ccs_mut_schritte WHERE mut_schritte_typ = '$this->workflow_typ_id' ORDER BY mut_schritte_schritt ASC");
		$db2 = clone($this->db);
    while($data = $this->db->get_next_res())
    {
      //manage single quotes
      foreach ($data as $eingabe => $val ) { if(!is_object($data[$eingabe])) { $data[$eingabe] = str_replace("'","&#39;",$val); } }
      
      $db2->sql_query("INSERT INTO ccs_mut_infos (mut_infos_nr,mut_infos_schritt,
                                              mut_infos_herkunft, mut_infos_person_id,
                                              mut_infos_beschreibung, mut_infos_var,
                                              mut_infos_text, mut_infos_reminder,
                                              mut_infos_bedingung,mut_infos_status, mut_infos_typ)
                                     VALUES   ($mut_id,'$data[mut_schritte_schritt]',
                                              '$data[mut_schritte_id]','$data[mut_schritte_pers_id]',
                                              '$data[mut_schritte_beschreibung]','$data[mut_schritte_var]',
                                              '$data[mut_schritte_text]','$data[mut_schritte_reminder]',
                                              '$data[mut_schritte_bedingung]','Neu','$data[mut_schritte_typ]')");
    }
		return $mut_id;
	}


	public function start()
	{
		if($this->mut_id=='') { throw new exception("Function start: Workflow not loaded"); }
 		$this->db->sql_query("UPDATE ccs_mutationen SET mut_status='Offen' WHERE mut_id='$this->mut_id'");
		$this->load_existing($this->mut_id);

    //Falls der Antrag für eine andere Person gestartet wurde, diese informieren
    if($this->request_user->id!=$this->user->id) { $this->send_mail("OTHER_USER",$this->user->id,$this->mut_id); }
		return $this->continue_wf();
	}

	public function continue_wf()
	{
		if($this->mut_id=='') { throw new exception("Function continue_wf: Workflow not loaded"); }
		$x = "";
		$ret_val = $this->check_next_steps();
		if(is_array($ret_val)) //Users are returned
		{
			foreach($ret_val as $wf_user)
			{
				$x.= $wf_user->fullname."<br>";
			}
		}
		else //Text is returned
		{
			$x = $ret_val;
		}
		return $x;
	}

	public function abort_wf($user,$reason,$with_mails=true)
	{
		if($this->mut_id=='') { throw new exception("Function abort_wf: Workflow not loaded"); }
		$db = clone($this->db);
		$x = "";
		$allready_sent = array();
		//Remove open steps
    $this->db->sql_query("DELETE FROM ccs_mut_infos WHERE mut_infos_status='Neu' AND mut_infos_nr='".$this->mut_id."'");

		//On reject this steps are allready done
		if($this->status=='Offen')
		{
			//Insert information for abort
	    $db->sql_query("INSERT INTO ccs_mut_infos (mut_infos_nr,mut_infos_schritt, mut_infos_person_id, mut_infos_person, mut_infos_datum, mut_infos_aktion, mut_infos_status, mut_infos_begruendung, mut_infos_typ,mut_infos_var)
	                                	VALUES   ('$this->mut_id','99','".$user->id."','$user->fullname',GETDATE(), 'Storniert','Abgeschlossen','$reason','$this->workflow_typ_id','Auftrag')");

			//Inform allready involved people
			$this->db->sql_query("SELECT * FROM ccs_mut_infos WHERE mut_infos_nr='$this->mut_id' AND mut_infos_schritt!='99' AND (mut_infos_aktion NOT LIKE 'Übersprungen' OR mut_infos_aktion is NULL)");
			while($d = $this->db->get_next_res())
			{
		    if(in_array($d['mut_infos_person_id'],$allready_sent)===FALSE)
		    {
		      if($with_mails) { $this->send_mail('STORNIERUNG',$d['mut_infos_person_id'],$d['mut_infos_id']); }
		      $allready_sent[] = $d['mut_infos_person_id'];
		    }
		    if($d['mut_infos_aktion'] == '')
		    {
			      $db->sql_query("UPDATE ccs_mut_infos SET mut_infos_aktion='Storniert', mut_infos_status='Abgeschlossen'
			                      WHERE mut_infos_id='$d[mut_infos_id]'");
		    }
			}
      if($with_mails) { $this->send_mail('STORNIERUNG',$this->request_user->id); }

			//Update Workflow information
			$db->sql_query("UPDATE ccs_mutationen SET mut_status='Storniert', mut_enddatum=GETDATE() WHERE mut_id='$this->mut_id'");
		}

		$this->execute_deny_file();


		//Write PDF File
		$this->write_pdf();

		return null;
	}


	public function get_step_uid($step_id)
	{
		$db = clone($this->db);
	  $step = $db->sql_query_with_fetch("SELECT * FROM ccs_mut_infos WHERE mut_infos_id='$step_id'");
	  $sID = $step['mut_infos_person_id'];
 		$this->load_existing($step['mut_infos_nr']);
	  $mutation = $db->sql_query_with_fetch("SELECT * FROM ccs_mutationen WHERE mut_id='$step[mut_infos_nr]'");

	  if($sID>0)
	  {
	    //User-Index bigger than 0 means it's a real person and return his ID
	    $tid = $sID;
	  }
	  else
	  {
	    if ($sID>-10)
	    {
	      switch($sID)
	      {
	        //related user
	        case "-1":
	          $tid = $mutation['mut_user_id'];
	          break;

	        //supervisor
	        case "-2":
	          $data = $db->sql_query_with_fetch("SELECT * FROM users WHERE user_id='".$this->user->id."'");
	          $tid = $data['user_vorgesetzter'];
	          break;

	        //Cost Center manager
	        case "-3":
	          $data = $db->sql_query_with_fetch("SELECT * FROM users
	                                                    LEFT JOIN kostenstellen ON users.user_kostenstelle=kostenstellen.ks_id
	                                                    WHERE user_id='".$this->user->id."'");
	          $tid = $data['ks_leiter_user_id'];
	          break;

	        //Geschäftsleitungsteil
	        case "-4":
	          $data = $db->sql_query_with_fetch("SELECT * FROM users
	                                                    LEFT JOIN kostenstellen ON users.user_kostenstelle=kostenstellen.ks_id
	                                                    WHERE user_id='".$this->user->id."'");
	          $tid = $data['ks_gl_id'];
	          break;

	        //Antragssteller
	        case "-5":
	          $tid = $this->request_user->id;
	          break;

	        //BU Leiter
	        case "-6":
	          $data = $db->sql_query_with_fetch("SELECT * FROM users
	                                                    LEFT JOIN kostenstellen ON users.user_kostenstelle=kostenstellen.ks_id
	                                                    WHERE user_id='".$this->user->id."'");
	          $tid = $data['ks_bl_id'];
	          break;
	      }
	    }
	    else
	    {
	      //if index is -10 or smaller, the person is defined by an external file
	      $sID = $sID - $sID - $sID; //from -10 to 10
				//include external File, set variable $tid
				if(file_exists(level."app_workflows/def_files/def_".$sID.".php"))
				{
					include(level."app_workflows/def_files/def_".$sID.".php");
				}
	    }
	  }
	  if(!isset($tid) || $tid=='') { $tid = '241'; } //set ID as default to Admin-ID
		return $tid;
	}

	public function get_Stv($user_id)
	{
		$db = clone($this->db);
	  //Falls eine Stellvertretung definiert ist, dessen Daten zurückgeben
		$myUser = new user(clone($this->db),$user_id);
		$i = 0;
		for($i;$i<5;$i++) //not more than 5 persone (to avoid recursive problems)
		{
		  $stv_result = $db->sql_query("SELECT * FROM ccs_users
		                          LEFT JOIN users ON ccs_users.ccs_users_stv_id=users.user_id
		                          WHERE ccs_users_user_id='$myUser->id'");
		  if ($db->count()>0)
		  {
		    $stv_daten = $db->get_next_res();
		    if ($stv_daten['ccs_users_stv_aktiv']==TRUE AND $stv_daten['ccs_users_stv_id']!=NULL) { $myUser = new user($this->db,$stv_daten['ccs_users_stv_id']); }
			}
		}
		return $myUser;
	}

	public function check_next_steps()
	{
    $arr_next_user = array();
		$info = null;

		if($this->mut_id=='') { throw new exception("Function check_next_steps: Workflow not loaded"); }
    $last_step=null;
	  If (strpos($this->status,'Offen')!==FALSE)
	  {
	    $this->db->sql_query("SELECT * FROM ccs_mut_infos
	                            WHERE mut_infos_nr='$this->mut_id' AND mut_infos_status='Gesendet'");

	    //Falls alle aktivierten Schritte erledigt sind, neue Schritte aktivieren
	    if ($this->db->count() == 0)
	    {
				$end=0;
				$db3 = clone($this->db);
	      $db3->sql_query("SELECT * FROM ccs_mut_infos
	                              WHERE mut_infos_nr='$this->mut_id' AND mut_infos_status='Neu'
	                              ORDER BY mut_infos_schritt ASC");
				$db2 = clone($this->db);
	      while ($daten=$db3->get_next_res())
	      {
	        $keineFreigabe=null; $begr = null; $status = null; $action = null; //Reset variables

	        //Wenn alle Schritte mit der gleichen Nummer abgeschlossen sind und min. ein Mail (ausser Infomails) versendet wurde, Schleife abbrechen
	        if($daten['mut_infos_schritt']!=$last_step AND $last_step!='' AND $end=='1') { break; }

	        $last_step = $daten['mut_infos_schritt'];
	        //get the responsible person for this step
	        $uid = $this->get_step_uid($daten['mut_infos_id']);

	        //check for deputies
	        $effuser = $this->get_Stv($uid);

	        //************************************************************************************************************
	        //Approval required only if...
	        //************************************************************************************************************
 	        $keineFreigabe='0';

 	        //Falls ein if_file für diesen Schritt existiert ausführen
 	        if($daten['mut_infos_bedingung']!='')
 	        {
						if(file_exists(level."app_workflows/if_files/".$daten['mut_infos_bedingung']))
						{
							include(level."app_workflows/if_files/".$daten['mut_infos_bedingung']);
              if(trim($begr)!='') {	$begr.=' / '; }
						}
	        }

	        if (!$effuser && $keineFreigabe != '1')
	        {
	          $effuser = new user($this->db,241);
	          $begr.= $this->t->translate('Aufgrund fehlender Person an Admin übergeben')." / ";
	        }

	        //************************************************************************************************************
	        if(substr_count($daten['mut_infos_var'],"Freigabe")>0)
	        {
            //Falls der betroffene User in den Mutationen schon einmal als Freigabe vorgekommen ist, keine Freigabe mehr verlangen
            $muts = $db2->sql_query("SELECT * FROM ccs_mut_infos
                                  				WHERE mut_infos_nr='$this->mut_id' AND mut_infos_status='Abgeschlossen' AND mut_infos_person_id='$effuser->id'
																																							AND mut_infos_var LIKE '%Freigabe%' AND mut_infos_aktion='Ja'");

						if($db2->count()>0) { $keineFreigabe='1'; $begr.= 'Hat schon einmal freigegeben / '; }

            //Falls der betroffene User der begünstigte User ist, keine Freigabe mehr verlangen
            if ($this->user->id==$uid)
						{
							$keineFreigabe='1'; $begr.= 'Ist betroffene Person / ';
						}
						else
						{
              if ($this->user->id==$effuser->id) { $keineFreigabe='1'; $begr.= 'Stv. der betroffenen Person / '; }
						}

            //Falls der betroffene User der Startuser ist, keine Freigabe mehr verlangen
            if ($this->request_user->id==$uid)
            {
              $keineFreigabe='1'; $begr.= $this->t->translate('Hat Antrag gestartet')." / ";
            }
            else
            {
              if ($this->request_user->id==$effuser->id) { $keineFreigabe='1'; $begr.= 'Stv. des Antragsstellers / '; }
            }
	        }
					if(substr($begr,-3)==' / ') { $begr = substr($begr,0,-3); }
          if($keineFreigabe!='1') { $status='Gesendet'; $action=''; } else { $status='Abgeschlossen'; $action='Übersprungen'; }

					//Special Text for typ Information
					if ($daten['mut_infos_var']=='Info') { if($keineFreigabe!='1') { $status='Abgeschlossen'; $action='Informiert'; } else { $status='Abgeschlossen'; $action='Übersprungen'; }}

          $db2->sql_query("UPDATE ccs_mut_infos SET mut_infos_datum=GETDATE(),mut_infos_status='$status',
                                      mut_infos_person='$effuser->fullname', mut_infos_person_id='$effuser->id',
																			mut_infos_aktion='$action', mut_infos_begruendung='$begr'
	                        WHERE mut_infos_id='$daten[mut_infos_id]'");
					if($action!='Übersprungen')
					{
						$this->load_step($daten['mut_infos_id']);
	          $this->send_mail($daten['mut_infos_var'],$effuser->id,$daten['mut_infos_id']);
						if($action!='Informiert') { $arr_next_user[] = $effuser; }
						if($daten['mut_infos_var']!='Info') { $end='1'; }
					}
	      }
	    }
		}
    //**************************************************************************************************************************
    //check if there are any steps left, otherwise close the request
    //**************************************************************************************************************************
    $this->db->sql_query("SELECT * FROM ccs_mut_infos WHERE mut_infos_nr='$this->mut_id' AND mut_infos_status!='Abgeschlossen'");
    if($this->db->count()==0) { $info = $this->close($this->mut_id); }
		if(isset($arr_next_user) && count($arr_next_user)>0) { return $arr_next_user; } else { return $info; }
	}


	//Close workflow without information to involved people
	public function close_as_admin($user,$reason)
	{
		if($this->mut_id=='') { throw new exception("Function abort_wf: Workflow not loaded"); }
		$db = clone($this->db);
		$x = "";
		//Remove open steps
    $this->db->sql_query("DELETE FROM ccs_mut_infos WHERE mut_infos_status='Neu' AND mut_infos_nr='".$this->mut_id."'");

		//On reject this steps are allready done
		if($this->status=='Offen')
		{
			//Insert information for abort
	    $db->sql_query("INSERT INTO ccs_mut_infos (mut_infos_nr,mut_infos_schritt, mut_infos_person_id, mut_infos_person, mut_infos_datum, mut_infos_aktion, mut_infos_status, mut_infos_begruendung, mut_infos_typ,mut_infos_var)
	                                	VALUES   ('$this->mut_id','99','".$user->id."','$user->fullname',GETDATE(), 'Durch Admin geschlossen','Abgeschlossen','$reason','$this->workflow_typ_id','Auftrag')");

			//Inform allready involved people
			$this->db->sql_query("SELECT * FROM ccs_mut_infos WHERE mut_infos_nr='$this->mut_id' AND (mut_infos_aktion NOT LIKE 'Übersprungen' OR mut_infos_aktion is NULL)");
			while($d = $this->db->get_next_res())
			{
		    if($d['mut_infos_aktion'] == '')
		    {
			      $db->sql_query("UPDATE ccs_mut_infos SET mut_infos_aktion='Durch Admin geschlossen', mut_infos_status='Abgeschlossen'
			                      WHERE mut_infos_id='$d[mut_infos_id]'");
		    }
			}

			//Update Workflow information
			$db->sql_query("UPDATE ccs_mutationen SET mut_status='Durch Admin geschlossen', mut_enddatum=GETDATE() WHERE mut_id='$this->mut_id'");
		}

		//Write PDF File
		$this->write_pdf();

		return null;
	}


	//Close workflow
	public function close($mut_id)
	{
		$this->load_existing($mut_id);
		if($this->check_execution_date_expired())
		{
			$this->myLog->write_to_log("Info","Close workflow ".$this->mut_id,"Start closing");
			$this->db->sql_query("UPDATE ccs_mutationen SET mut_status='Abgeschlossen', mut_enddatum = GETDATE() WHERE mut_id='$mut_id'");
 			$this->write_pdf($mut_id);
      $this->send_mail('ABSCHLUSS',$this->request_user->id);
			if($this->status!='Offen (Anpassungen durchgeführt)')
			{
				$this->execute_accept_file();
			}
 			$this->myLog->write_to_log("Info","Close workflow ".$this->mut_id,"Closing finished successfully");
			return "Antrag abgeschlossen";
		}
		else
		{
	    if($this->status!='Offen (Anpassungen durchgeführt)')
	    {
				$this->db->sql_query("UPDATE ccs_mutationen SET mut_status='Warten auf Ausführdatum' WHERE mut_id='$mut_id'");
	    }
	    else
	    {
				$this->db->sql_query("UPDATE ccs_mutationen SET mut_status='Warten auf Ausführdatum (Anpassungen durchgeführt)' WHERE mut_id='$mut_id'");
	    }
			return "Workflow abgeschlossen, warten auf Ausführdatum";
		}
	}

	public function execute_deny_file()
	{
		if(file_exists(level."app_workflows/workflows/".$this->form_folder."/deny.php"))
		{
			$this->myLog->write_to_log("Info","Close workflow ".$this->mut_id,"Execute Deny-File");
			include(level."app_workflows/workflows/".$this->form_folder."/deny.php");
		}
	}

	public function execute_accept_file()
	{
		if(file_exists(level."app_workflows/workflows/".$this->form_folder."/accept.php"))
		{
			$this->myLog->write_to_log("Info","Close workflow ".$this->mut_id,"Execute Accept-File");
			include(level."app_workflows/workflows/".$this->form_folder."/accept.php");
		}
	}

	public function check_execution_date_expired()
	{
		if($this->execution_date_field!='')
		{
	    $this->db->sql_query("SELECT * FROM ".$this->sql_table." WHERE (".$this->execution_date_field." < DATEADD(day, 0, GETDATE())) AND (mut_id = '".$this->mut_id."')");
			if($this->db->count()=='1') { return true; } else { return false; }
		}
		else
		{
			return true;
		}
	}

	public function write_pdf()
	{
		//Reload data
		$this->load_existing($this->mut_id);
		require_once('html2pdf/html2pdf.class.php');
    $html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8',array(10, 10, 10, 10));
    $html2pdf->setDefaultFont('Arial');
		$html2pdf->pgwidth = 180;

    $txt = "<html>";
    $txt.= "  <head>";
    $txt.= "		<link rel='stylesheet' type='text/css' href='".level."inc/css/styles.css'/>";
    $txt.= "  	<style>";
    $txt.= "  		td.schritte { font-size:6pt; }";
    $txt.= "  	</style>";
    $txt.= "  </head>";
    $txt.= "  <body style='width:700px;'>";
    $txt.= "    <div>";
    $txt.= "       <table border='0'>\n";
    $txt.= "          <tr>\n";
    $txt.= "            <td style='width:500px;'>\n";
    $txt.= "              <a href='".level."index.php'><img style='width:200px;' src='".level."inc/imgs/ccs_logo.jpg' alt='CentralChangeSystem'/></a>\n";
    $txt.= "            </td>\n";
    $txt.= "            <td><img style='width:200px;' src='".level."inc/imgs/company_logo.jpg' alt='Hitachi Zosen Inova'/></td>\n";
    $txt.= "          </tr>\n";
    $txt.= "       </table>\n";
    $txt.= "       <hr>\n";
    $txt.= "       <table border='0'>\n";
    $txt.= "          <tr><td style='width:550px;font-size:16pt;font-weight:bold;'>Abschlussbericht</td><td></td></tr>";

		$db = clone($this->db);
		$data = $db->sql_query_with_fetch("SELECT CONVERT(char(10),mut_startdatum,104) as s_datum, CONVERT(char(10),mut_enddatum,104) as e_datum FROM ccs_mutationen WHERE mut_id='$this->mut_id'");

    $txt.= "          <tr><td style='font-size:12pt;'>".$this->t->translate('Nr.')." ".$this->mut_id." / ".$this->title."</td><td style='font-size:10pt;'>".$this->t->translate('Startdatum').":</td><td style='font-size:10pt;'>".$data['s_datum']."</td></tr>";
    $txt.= "          <tr><td style='font-size:14pt;font-weight:bold;'>".$this->t->translate('Status').": ".$this->status."</td><td style='font-size:10pt;'>".$this->t->translate('Enddatum').":</td><td style='font-size:10pt;'>".$data['e_datum']."</td></tr>";
    $txt.= "       </table>\n";
    $txt.= "       <hr>\n";
		$txt.= "		   <div style='width:100%;'>".$this->get_user_info()."</div>";
    $txt.= "       <hr style='color:gray;height:1px;'>\n";
		$txt.= "		   <div style='width:100%;'>".$this->get_text()."</div>";
    $txt.= "       <hr style='color:gray;height:1px;'>\n";
		$txt.= "		   <div style='width:100%;'>".$this->get_workflow_history()."</div>";
		$txt.= "    </div>";
		$txt.= "  </body>";
		$txt.= "</html>";
    $html2pdf->writeHTML($txt);
    $html2pdf->Output(level.'app_workflows/workflows/'.$this->form_folder.'/finished_workflows/'.$this->mut_id.'.pdf','F');
		return null;
	}

	public function get_user_selection()
	{
    $myHTML = new html($this->db);
    $myPage = new page($this->db);
    $page = new header_mod;
		$page->change_parameter('action','user_change');
    $txt = "<form name='user_selection' action='".$page->get_link()."' method='POST'>";
		$txt.= "<table style='width:100%;background-color:#FAFAFA;border-top:5px solid #EEE;border-bottom:5px solid #EEE;margin-top:10px;margin-bottom:10px;'>
		  			<tr>";
		$txt.= "<td style='width:200px;'><h2>".$this->t->translate("Mitarbeiter auswählen")."</h2></td><td>";
		if(isset($this->sql_user_selection))
    {
      $this->db->sql_query($this->sql_user_selection);
    } 
    else
    {
      $this->db->sql_query($myPage->get_setting('sql_user_selection'));
    }
		if(!isset($_GET['user_id']) || $_GET['user_id']=='') { $_GET['user_id'] = $_SESSION['login_user']->id; }

		$txt.=$myHTML->get_selection($this->db,'user_id','user_id','user_fullname',"onchange='document.forms.user_selection.submit();'");
		$txt.= "</td>";
    //Falls der User nicht der eigenen ist wird eine Information gezeigt
		if(isset($_GET['user_id']))
		{
			if($_SESSION['login_user']->id != $_GET['user_id'])
	    {
	        $txt.= "<td>".$myPage->showInfo($this->t->translate("Sie wollen eine andere Person als sich selbst ändern. Nach dem Abschicken wird die Person über den Antrag informiert und ihr Name genannt. Die Übersicht über den Status behalten weiterhin Sie."))."</td>";
	    }
		}
		$txt.= "</tr></table></form>";
		return $txt;
	}

	//Displays the form of the loaded workflow
	public function get_form()
	{
		if(file_exists(level."app_workflows/workflows/".$this->form_folder."/create_form.php"))
		{
			$this->html_code = "<div id='workflow_form'><form id='workflow'>";
			include_once(level."app_workflows/workflows/".$this->form_folder."/create_form.php");
			if(isset($_GET['action']) && $_GET['action']=='extend_form')
			{
				$h = new header_mod();
				$h->remove_parameter('action');
				$this->html_code.= "<hr/><a class='ccs_button gray' onClick=\"window.location.assign('".$h->get_link()."');\">".$this->t->translate('Abbrechen')."</a>";
				$this->html_code.= "<a class='ccs_button blue' onClick=\"extend_row();\">Speichern und weiter...</a>";
			}
			else
			{
				$this->html_code.= "<hr/><a class='ccs_button gray' onClick=\"window.location.assign('../../');\">".$this->t->translate('Abbrechen')."</a>";
				$this->html_code.= "<a class='ccs_button green' onClick=\"new_row();\">".$this->t->translate('Bestätigen')."</a>";
			}
			if($this->is_multipart) { $this->html_code = str_replace("<form id='workflow'>","<form id='workflow' enctype='multipart/form-data'>",$this->html_code); }
			$this->html_code.= "</form></div>";
	    if(!IS_AJAX)
	    {
	      $this->html_code.= "  <div id='edit_message' class='wf'></div>\n";
				$this->html_code.="<script>
	            var parameters='$_SERVER[QUERY_STRING]';
	            function set_parameter(param,val)
	            {
	              var re = new RegExp('&'+param+'(\\=[^&]*)?(?=&|$)|^'+param+'(\\=[^&]*)?(&|$)','g');
	              parameters = parameters.replace(re, '');
	              if(val!='') { parameters = parameters + '&' + param + '=' + val}
	              //alert(parameters);
	            }
	            function close_myinfo()
	            {
	              $('#block_div').remove();
								$('#workflow_form').css('opacity','1');
	              $('#edit_message').fadeTo(300,0, function()
								{
									$('#edit_message').hide(); //Without this command the days in calendar could note be selected
								});
							}
							";

					if(isset($_GET['action']) && $_GET['action']=='extend_form')
					{
						$h = new header_mod();
						$h->remove_parameter('action');
            $this->html_code.="
	            function exit()
	            {
	              window.location.assign('".$h->get_link()."');
							}

							function extend_row()
	            {
								$('*').removeAttr('disabled');
	              var data = $('#workflow').serialize();
								$('.disabled').prop('disabled', true);
								$('#workflow_form').css('opacity','0.2');
								//add a div to block all content in the form
								$('#workflow_form').append('<div id=\'block_div\' style=\'position: absolute;top:0;left:0;width: 100%;height:100%;z-index:2;\'></div>');
	              set_parameter('ajax','edit');
	              set_parameter('id','0');
								$('#edit_message').html('<span style=\'color:gray;font-size:16pt;\'>Ergänze Workflow Daten</span><hr/><table style=\'width:100%\'><tr><td class=\'wf_status\'>".$this->t->translate('Formular auf Vollständigkeit überprüfen').":</td><td id=\'check_form\'></td></tr><tr><td>".$this->t->translate('Daten speichern').":</td><td class=\'wf_status\' id=\'save_data\'></td></tr></table><div id=\'close_link\' style=\'display:none;\'><hr/><a class=\'ccs_button gray\' onClick=\'close_myinfo();\'>".$this->t->translate('Schliessen')."</a></div><div id=\'exit_link\' style=\'display:none;\'><hr/><a class=\'ccs_button blue\' onClick=\'exit();\'>".$this->t->translate('Weiter zur Antragsbestätigung')."</a></div>');
	              $('#edit_message').fadeTo(300,1);
								$('#check_form').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
	              $.ajax({
	                type: 'POST',
	                url: '$_SERVER[PHP_SELF]?step_id=$_GET[step_id]&user_id=".$this->user->id."&action=check_form',
	                data: data,
	                success: function(response)
	                {
	                  if(response!='')
										{
											$('#check_form').addClass('wf_status_error');
											$('#check_form').html(response);
											$('#save_data').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
				              $('#close_link').show();
										}
										else
										{
											$('#check_form').html('<img src=\'".level."inc/imgs/erledigt.gif\' alt=\'OK\'/>');
											$('#save_data').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
				              $.ajax({
				                type: 'POST',
				                url: '$_SERVER[PHP_SELF]?step_id=$_GET[step_id]&user_id=".$this->user->id."&action=save_data',
				                data: data,
				                success: function(response)
				                {
				                  if(response!='')
													{
														$('#save_data').html(response);
							              $('#close_link').show();
													}
													else
													{
														$('#save_data').html('<img src=\'".level."inc/imgs/erledigt.gif\' alt=\'OK\'/>');
							              $('#exit_link').show();
													}
												}
											});
										}
									}
								});
	            }";
					}
					else
					{
            $this->html_code.="
	            function exit()
	            {
	              window.location.assign('../../index.php');
							}
							function show_wf_info()
							{
								$('#edit_message').html('<div id=\'wf_content\'></div><div id=\'close_link\'><hr/><a class=\'ccs_button green\' onClick=\'close_myinfo();\'>".$this->t->translate('Schliessen')."</a></div>');
	              $('#edit_message').fadeTo(300,1);
								$('#wf_content').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
	              $.ajax({
	                type: 'GET',
	                url: '$_SERVER[PHP_SELF]?id=$_GET[id]&user_id=".$this->user->id."&action=get_wf_preview',
	                success: function(response)
	                {
	                  if(response!='')
										{
											$('#wf_content').html(response);
										}
									}
								});
							}

	            function new_row()
	            {
								$('*').removeAttr('disabled');
	              var data = new FormData($('#workflow')[0]);
								$('.disabled').prop('disabled', true);
								$('#workflow_form').css('opacity','0.2');
								//add a div to block all content in the form
								$('#workflow_form').append('<div id=\'block_div\' style=\'position: absolute;top:0;left:0;width: 100%;height:100%;z-index:2;\'></div>');
	              set_parameter('ajax','edit');
	              set_parameter('id','0');
								$('#edit_message').html('<span style=\'color:gray;font-size:16pt;\'>".$this->t->translate('Starte Workflow')."</span><hr/><table style=\'width:100%\'><tr><td class=\'wf_status\'>".$this->t->translate('Formular auf Vollständigkeit überprüfen').":</td><td id=\'check_form\'></td></tr><tr><td class=\'wf_status\'>".$this->t->translate('Daten speichern').":</td><td id=\'save_data\'></td></tr><tr><td class=\'wf_status\'>".$this->t->translate('Antragsnummer generieren').":</td><td id=\'get_number\' class=\'wf_status_info\'></td></tr><tr><td class=\'wf_status\'>".$this->t->translate('Workflow übergeben an').":</td><td id=\'check_next_step\' class=\'wf_status_info\'></td></tr></table><div id=\'close_link\' style=\'display:none;\'><hr/><a class=\'ccs_button green\' onClick=\'close_myinfo();\'>".$this->t->translate('Schliessen')."</a></div><div id=\'exit_link\' style=\'display:none;\'><hr/><a class=\'ccs_button green\' onClick=\'exit();\'>".$this->t->translate('Verlassen')."</a></div>');
	              $('#edit_message').fadeTo(300,1);
								$('#check_form').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
	              $.ajax({
	                url: '$_SERVER[PHP_SELF]?id=$_GET[id]&user_id=".$this->user->id."&action=check_form',
	                type: 'POST',
	                data: data,
									cache: false,
									async: false,
					        contentType: false,
					        processData: false,
	                complete: function(response)
	                {
										var response = response.responseText;
	                  if(response!='')
										{
											$('#check_form').addClass('wf_status_error');
											$('#check_form').html(response);
											$('#save_data').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
											$('#get_number').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
											$('#check_next_step').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
				              $('#close_link').show();
										}
										else
										{
											$('#check_form').html('<img src=\'".level."inc/imgs/erledigt.gif\' alt=\'OK\'/>');
											$('#save_data').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
				              $.ajax({
				                type: 'POST',
				                url: '$_SERVER[PHP_SELF]?id=$_GET[id]&user_id=".$this->user->id."&action=save_data',
				                data: data,
												cache: false,
												async: false,
								        contentType: false,
								        processData: false,
				                complete: function(response)
				                {
													var response = response.responseText;
				                  if(response.substr(0,2)!='>>')
													{
   													$('#save_data').addClass('wf_status_error');
														$('#save_data').html(response);
														$('#get_number').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
														$('#check_next_step').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
							              $('#close_link').show();
													}
													else
													{
														if(response.indexOf(';')!=-1)
														{
															user_id = response.substr(response.indexOf(';')+1);
															wf_no = response.substr(2,response.indexOf(';')-2);
														}
														else
														{
															user_id=".$this->user->id.";
															wf_no = response.substr(2);
														}
														$('#save_data').html('<img src=\'".level."inc/imgs/erledigt.gif\' alt=\'OK\'/>');
														$('#get_number').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
							              $.ajax({
							                type: 'GET',
							                url: '$_SERVER[PHP_SELF]?id=$_GET[id]&user_id='+user_id+'&mut_data_id='+wf_no+'&action=get_number',
							                success: function(response)
							                {
																$('#get_number').html(document.createTextNode(response));
																$('#check_next_step').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
									              $.ajax({
									                type: 'GET',
									                url: '$_SERVER[PHP_SELF]?id=$_GET[id]&mut_id='+response+'&action=start_workflow',
									                success: function(response)
									                {
																		$('#check_next_step').html(response);
											              $('#exit_link').show();
																	}
																});
															}
														});
													}
												}
											});
										}
									}
								});
	            }";


						}
            $this->html_code.="</script>";
	    }
	    //*********************************************************************************************
		}
		else
		{
			$this->html_code = $this->t->translate("<div style='color:red;font-size:16pt;padding-top:10px;padding-bottom:10px;'>create_form.php Datei nicht gefunden!</div>");
		}
    return $this->html_code;
	}

	public function get_simple_text($length=150)
	{
		if($this->mut_id=='') { throw new exception("Workflow not loaded"); }
		$wf_txt = $this->get_text();
		$wf_txt = preg_replace("'<[\/\!]*?[^<>]*>'si","",$wf_txt);
		if(strlen($wf_txt)>$length) {	$wf_txt = substr($wf_txt,0,$length)."..."; }
		//if there is no space within the half of the requested lenght, add one
		if(substr_count(substr($wf_txt,0,$length/2)," ")== 0) { $wf_txt = substr($wf_txt,0,$length/2)." ".substr($wf_txt,$length/2); }
		return $wf_txt;
	}

	//Displays the text of the loaded workflow
	public function get_user_info($for_mail=false)
	{
		if($this->mut_id=='') { throw new exception("Workflow not loaded"); }
		$x = "<h2>".$this->t->translate('Benutzerinformationen')."</h2>";
		$x.= "<table>";
  	$x.= "<tr><td style='width:200px;font-weight:bold;'>".$this->t->translate('Antragssteller')."</td><td>".$this->request_user->fullname." (<a href='".level."app_it_db/aw_user.php?user=".$this->request_user->login."' target='_blank'>".$this->request_user->login."</a>)</td>";
		if(isset($this->request_user->cost_unit)) { $x.= "<td> | ".$this->request_user->cost_unit->shortname."</td>"; }
		$x.= "<td style='width:300px;'> | ".$this->request_user->funktion_full."</td></tr>";

		$x.= "<tr><td style='font-weight:bold;'>".$this->t->translate('Betroffener Mitarbeiter')."</td><td>".$this->user->fullname." (<a href='".level."app_it_db/aw_user.php?user=".$this->user->login."' target='_blank'>".$this->user->login."</a>)</td>";
		if(isset($this->user->cost_unit)) { $x.= "<td> | ".$this->user->cost_unit->shortname."</td>"; }
		$x.= "<td style='width:300px;'> | ".$this->user->funktion_full."</td></tr>";
		$x.= "</table>";
		//Replaces relativ image paths to absolute (all paths in CCS are prefixed by the level to the top folder, therefor it can be translated simply to absolute)
		//Problem occured by the HTML2PDF Libraray, which don't work with absolute paths, but for pictures in E-Mail its neccessary
		if($for_mail)
		{
			$x = str_replace("../","",$x);
			$x = preg_replace("#(<\s*a\s+[^>]*href\s*=\s*[\"'])(?!https)([^\"'>]+)([\"'>]+)#", '$1'.CCS_PATH.'$2$3', $x);
		}
    return $x;
	}

	//Displays the text of the loaded workflow
	public function get_text($for_mail=false)
	{
		if($this->mut_id=='') { throw new exception("Workflow not loaded"); }
		if(file_exists(level."app_workflows/workflows/".$this->form_folder."/get_text.php"))
		{
			include(level."app_workflows/workflows/".$this->form_folder."/get_text.php");
			$wf_txt = $this->t->translate("<h2>Der Antrag</h2>").$wf_txt;
		}
		else
		{
			$wf_txt.= $this->t->translate("<div style='color:red;font-size:16pt;padding-top:10px;padding-bottom:10px;'>get_text.php Datei nicht gefunden!</div>");
		}
		//Replaces relativ image paths to absolute (all paths in CCS are prefixed by the level to the top folder, therefor it can be translated simply to absolute)
		//Problem occured by the HTML2PDF Libraray, which don't work with absolute paths, but for pictures in E-Mail its neccessary
		if($for_mail)
		{
			$wf_txt = str_replace("../","",$wf_txt);
			$wf_txt = preg_replace("#(<\s*img\s+[^>]*src\s*=\s*[\"'])(?!https)([^\"'>]+)([\"'>]+)#", '$1'.CCS_PATH.'$2$3', $wf_txt);
		}
    return $wf_txt;
	}

	//Check the fields of the form
	public function check_form()
	{
		if(file_exists(level."app_workflows/workflows/".$this->form_folder."/check_form.php"))
		{
			include_once(level."app_workflows/workflows/".$this->form_folder."/check_form.php");
		}
		else
		{
			$err_txt = $this->t->translate("<div style='color:red;font-size:16pt;padding-top:10px;padding-bottom:10px;'>check_form.php Datei nicht gefunden!</div>");
		}
    return $err_txt; //set by the included File
	}

	//Check the data in the database
	public function check_db()
	{
		if(file_exists(level."app_workflows/workflows/".$this->form_folder."/check_db.php"))
		{
			include(level."app_workflows/workflows/".$this->form_folder."/check_db.php");
		}
		else
		{
			$err_txt = "";
// 			$err_txt = $this->t->translate("<div style='color:red;font-size:16pt;padding-top:10px;padding-bottom:10px;'>check_db.php Datei nicht gefunden!</div>");
		}
    return $err_txt; //set by the included File
	}

	//Save the data from the form to the database
	public function save_data()
	{
		if(file_exists(level."app_workflows/workflows/".$this->form_folder."/save_data.php"))
		{
			include_once(level."app_workflows/workflows/".$this->form_folder."/save_data.php");
		}
		else
		{
			$err_txt = $this->t->translate("<div style='color:red;font-size:16pt;padding-top:10px;padding-bottom:10px;'>save_data.php Datei nicht gefunden!</div>");
		}
    return $err_txt; //set by the included File
	}


	//Displays the the text for the current step
	public function get_step_text($for_mail=false)
	{
		$x = $this->step_text;
		$x = str_replace("(startuser)",$this->request_user->fullname,$x);
		$x = str_replace("(user)",$this->user->fullname,$x);
		$x = str_replace("(user_id)",$this->user->id,$x);

		//If a expression in brackets is in the text, search for a file with the same name. If it exists, include it and replace the text with the returned value in the variable $txt
		//(its usfull to add dynamic text in just one Step of the workflow)
		preg_match_all("/\(.*\)/i", $x,$matches);
		foreach($matches[0] as $myMatch)
		{
			$myMatch = strtolower(str_replace(')','',str_replace('(','',$myMatch)));
			if(file_exists(level."app_workflows/workflows/".$this->form_folder."/".$myMatch.".php"))
			{
				include(level."app_workflows/workflows/".$this->form_folder."/".$myMatch.".php"); //returns $txt
				$x = str_replace('('.$myMatch.')',$txt,$x);
			}
		}

		$x = trim($x);
		if(strpos(strtoupper($this->step_var),'ERW')!==FALSE)
		{
			$ret_val = $this->check_db();
			if($ret_val != "")
			{
	      $x.= "<p>".$this->t->translate("Im Antrag fehlen noch folgende Daten:")."</p>";
	      $x.= "<div style='font-size:12pt;font-weight:bold;'>".$ret_val."</div>";
			}
			if(!$for_mail) { $x.= $this->t->translate("<div id='extension_text'>Mit diesem")."<a class='ccs_button blue' href='?step_id=".$this->step_id."&action=extend_form'>".$this->t->translate("Link")."</a> ".$this->t->translate("können Sie Daten ergänzen</div>"); }
		}

		if($x!='')
		{
			$x = $this->t->translate("<h2>Ihre Aufgabe</h2>")."<p/>".$x;
		}
    return $x;
	}

	public function get_action_text()
	{
		if($this->step_var=='') { throw new exception("Workflow not loaded"); }
		$x = "";
		switch(strtoupper($this->step_var))
		{
	    case "AUFTRAG":
      	$x.= $this->t->translate("<div id='action_text'><p style='font-size:12pt;font-weight:bolder;'>Bitte bestätigen Sie mit dem folgenden Link, dass Sie ihre Aufgabe komplett ausgeführt haben</p>");
				$x.= $this->t->translate("<a class='ccs_button green' onClick=\"confirm_request();\">Auftrag durchgeführt</a>");
				$x.= $this->t->translate("<a class='ccs_button gray' onClick=\"transfer();\">Übergeben</a></div>");
	      break;

	    case "ERWEITERUNG MIT AUFTRAG":
	    case "AUFTRAG (FREIW. ERW.)":
				$ret_val = $this->check_db();
				$x.= "<div id='action_text'>";
				if($ret_val == "")
				{
	      	$x.= $this->t->translate("<p style='font-size:12pt;font-weight:bolder;'>Bitte bestätigen Sie mit dem folgenden Link, dass Sie ihre Aufgabe komplett ausgeführt haben</p>");
					$x.= $this->t->translate("<a class='ccs_button green' onClick=\"confirm_request();\">Auftrag durchgeführt</a>");
				}
				$x.= $this->t->translate("<a class='ccs_button gray' onClick=\"transfer();\">Übergeben</a></div>");
	      break;

	    case "FREIGABE":
      	$x.= $this->t->translate("<div id='action_text'><p style='font-size:12pt;font-weight:bolder;'>Sind Sie damit einverstanden?</p>");
				$x.= $this->t->translate("<a class='ccs_button green' onClick=\"say_yes();\">Ja</a>");
				$x.= $this->t->translate("<a class='ccs_button red' onClick=\"say_no();\">Nein</a>");
				$x.= $this->t->translate("<a class='ccs_button gray' onClick=\"transfer();\">Übergeben</a></div>");
	      break;

	    case "ERWEITERUNG MIT FREIGABE":
	    case "FREIGABE (FREIW. ERW.)":

				$ret_val = $this->check_db();
				if($ret_val == "")
				{
	      	$x.= $this->t->translate("<div id='action_text'><p style='font-size:11pt;font-weight:bolder;'>Sind Sie damit einverstanden?</p>");
					$x.= $this->t->translate("<a class='ccs_button green' onClick=\"say_yes();\">Ja</a>");
					$x.= $this->t->translate("<a class='ccs_button red' onClick=\"say_no();\">Nein</a>");
					$x.= $this->t->translate("<a class='ccs_button blue' onClick=\"transfer();\">Übergeben</a></div>");
				}
				else
				{
					$x.= $this->t->translate("<div id='action_text' style='margin-top:10px;'><a class='ccs_button red' onClick=\"say_no();\">Antrag ablehnen</a></div>");
				}
	      break;

			default:
				throw new exception("Selected workflowtyp not found (".$this->step_var.")");
		}
    if(!IS_AJAX)
    {
      $x.= "  <div id='edit_message' class='wf'></div>\n";
      $x.= "  <div id='no_message' style='overflow:auto;max-height:300px;display:none;z-index:100;padding:10px;'></div>\n";
      $x.="<script>
            var parameters='$_SERVER[QUERY_STRING]';

            function close_myinfo()
            {
              $('#block_div').remove();
              $('#edit_message').fadeTo(300,0, function()
							{
								$('#edit_message').hide(); //Without this command the days in calendar could note be selected
							});
						}
            function exit()
            {
              window.location.assign('../../index.php');
						}

            function say_yes()
            {
							$('#action_text').hide();
							$('#edit_message').html('<span style=\'color:gray;font-size:16pt;\'>".$this->t->translate("Workflow fortsetzen")."</span><hr/><table style=\'width:100%\'><tr><td class=\'wf_status\'>".$this->t->translate("Daten speichern").":</td><td id=\'save_data\'></td></tr><tr><td class=\'wf_status\'>".$this->t->translate("Workflow übergeben an").":</td><td id=\'check_next_step\' class=\'wf_status_info\'></td></tr></table><div id=\'close_link\' style=\'display:none;\'><hr/><a class=\'ccs_button green\' onClick=\'close_myinfo();\'>".$this->t->translate("Schliessen")."</a></div><div id=\'exit_link\' style=\'display:none;\'><hr/><a class=\'ccs_button green\' onClick=\'exit();\'>".$this->t->translate("Verlassen")."</a></div>');
              $('#edit_message').fadeTo(300,1);
							$('#save_data').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
              $.ajax({
                type: 'GET',
                url: '$_SERVER[PHP_SELF]?&step_id=$this->step_id&user_id=".$this->user->id."&action=say_yes',
                success: function(response)
                {
                  if(response!='')
									{
										$('#save_data').html(response);
										$('#check_next_step').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
									}
									else
									{
										$('#save_data').html('<img src=\'".level."inc/imgs/erledigt.gif\' alt=\'OK\'/>');
										$('#check_next_step').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
			              $.ajax({
			                type: 'GET',
			                url: '$_SERVER[PHP_SELF]?&mut_id=$this->mut_id&action=continue_workflow',
			                success: function(response)
			                {
												$('#check_next_step').html(response);
					              $('#exit_link').show();
											}
										});
									}
								}
							});
						}

            function confirm_request()
            {
							$('#action_text').hide();
							$('#edit_message').html('<span style=\'color:gray;font-size:16pt;\'>".$this->t->translate("Workflow fortsetzen")."</span><hr/><table style=\'width:100%\'><tr><td class=\'wf_status\'>".$this->t->translate("Daten speichern").":</td><td id=\'save_data\'></td></tr><tr><td class=\'wf_status\'>".$this->t->translate("Workflow übergeben an").":</td><td id=\'check_next_step\' class=\'wf_status_info\'></td></tr></table><div id=\'close_link\' style=\'display:none;\'><hr/><a class=\'ccs_button green\' onClick=\'close_myinfo();\'>".$this->t->translate("Schliessen")."</a></div><div id=\'exit_link\' style=\'display:none;\'><hr/><a class=\'ccs_button green\' onClick=\'exit();\'>".$this->t->translate("Verlassen")."</a></div>');
              $('#edit_message').fadeTo(300,1);
							$('#save_data').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
              $.ajax({
                type: 'GET',
                url: '$_SERVER[PHP_SELF]?&step_id=$this->step_id&user_id=".$this->user->id."&action=confirm_request',
                success: function(response)
                {
                  if(response!='')
									{
										$('#save_data').html(response);
										$('#check_next_step').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
									}
									else
									{
										$('#save_data').html('<img src=\'".level."inc/imgs/erledigt.gif\' alt=\'OK\'/>');
										$('#check_next_step').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
			              $.ajax({
			                type: 'GET',
			                url: '$_SERVER[PHP_SELF]?&mut_id=$this->mut_id&action=continue_workflow',
			                success: function(response)
			                {
												$('#check_next_step').html(response);
					              $('#exit_link').show();
											}
										});
									}
								}
							});
						}


            function say_no()
            {
							$('#action_text').hide();
							$('#no_message').html('<form id=\'form_reason\'>".$this->t->translate("<h2>Bitte geben Sie eine Begründung für die Ablehnung ein</h2>")."<textarea name=\'reason\' id=\'reason\' style=\'width:95%;height:50px;border-radius:15px;overflow:hidden;padding:10px;\'></textarea></form><br><a class=\'ccs_button red\' onClick=\'send_reason_and_continue();\'>Antrag ablehnen</a><a class=\'ccs_button gray\' onClick=\'$(\"#no_message\").hide(); $(\"#action_text\").fadeTo(300,1);\'>Abbrechen</a>');
              $('#no_message').fadeTo(300,1);
						}

            function transfer()
            {
							$('#action_text').hide();
							";
					    $myHTML = new html($this->db);
              $myPage = new page($this->db);
							$this->db->sql_query($myPage->get_setting('sql_user_employees_without_login_user'));
							$txt_user_selection = $myHTML->get_selection($this->db,'new_user','user_id','user_fullname','');
							$txt_user_selection = str_replace("\n","",$txt_user_selection);
							$txt_user_selection = str_replace("'","\'",$txt_user_selection);
							$x.="$('#no_message').html('<form id=\'form_reason\'>".$this->t->translate("<h2>Bitte wählen Sie einen neuen Mitarbeiter aus</h2>").$txt_user_selection.$this->t->translate("<h2>Bitte geben Sie eine Begründung für die Übergabe ein</h2>")."<textarea name=\'reason\' id=\'reason\' style=\'width:95%;height:50px;border-radius:15px;overflow:hidden;padding:10px;\'></textarea></form><br><a class=\'ccs_button blue\' onClick=\'send_reason_and_transfer();\'>Antrag übergeben</a><a class=\'ccs_button gray\' onClick=\'$(\"#no_message\").hide(); $(\"#action_text\").fadeTo(300,1);\'>Abbrechen</a>');
              $('#no_message').fadeTo(300,1);
						}

						function send_reason_and_continue()
						{
							$('body').append('<div id=\'block_div\' style=\'position: absolute;top:0;left:0;width: 100%;height:100%;z-index:2;background-color:gray;opacity:0.5;\'></div>');
							$('#edit_message').html('<span style=\'color:gray;font-size:16pt;\'>".$this->t->translate("Workflow ablehnen")."</span><hr/><table style=\'width:100%\'><tr><td style=\'width:500px;\'>".$this->t->translate("Begründung speichern + Workflow ablehnen").":</td><td id=\'save_data\'></td></tr></table><div id=\'close_link\' style=\'display:none;\'><hr/><a class=\'ccs_button gray\' onClick=\'close_myinfo();\'>".$this->t->translate("Schliessen")."</a></div><div id=\'exit_link\' style=\'display:none;\'><hr/><a class=\'ccs_button green\' onClick=\'window.location.reload(true);\'>".$this->t->translate("Verlassen")."</a></div>');
              $('#edit_message').fadeTo(300,1);
							$('#save_data').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
              var data = $('#form_reason').serialize();
              $.ajax({
                type: 'POST',
                url: '$_SERVER[PHP_SELF]?&step_id=$this->step_id&user_id=".$this->user->id."&action=say_no',
								data: data,
                success: function(response)
                {
                  if(response!='')
									{
										$('#save_data').html(response);
										$('#check_next_step').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
			              $('#close_link').show();
									}
									else
									{
										$('#save_data').html('<img src=\'".level."inc/imgs/erledigt.gif\' alt=\'OK\'/>');
			              $('#exit_link').show();
									}
								}
							});
						}

						function send_reason_and_transfer()
						{
							$('body').append('<div id=\'block_div\' style=\'position: absolute;top:0;left:0;width: 100%;height:100%;z-index:2;background-color:gray;opacity:0.5;\'></div>');
							$('#edit_message').html('<span style=\'color:gray;font-size:16pt;\'>".$this->t->translate("Workflow übergeben")."</span><hr/><table style=\'width:100%\'><tr><td style=\'width:500px;\'>".$this->t->translate("Begründung speichern + Workflow übergeben").":</td><td id=\'save_data\'></td></tr></table><div id=\'close_link\' style=\'display:none;\'><hr/><a class=\'ccs_button gray\' onClick=\'close_myinfo();\'>".$this->t->translate("Schliessen")."</a></div><div id=\'exit_link\' style=\'display:none;\'><hr/><a class=\'ccs_button green\' onClick=\'window.location.reload(true);\'>".$this->t->translate("Verlassen")."</a></div>');
              $('#edit_message').fadeTo(300,1);
							$('#save_data').html('<img src=\'".level."inc/imgs/laden.gif\' alt=\'Laden\'/>');
              var data = $('#form_reason').serialize();
              $.ajax({
                type: 'POST',
                url: '$_SERVER[PHP_SELF]?&step_id=$this->step_id&user_id=".$this->user->id."&action=transfer',
								data: data,
                success: function(response)
                {
                  if(response!='')
									{
										$('#save_data').html(response);
										$('#check_next_step').html('<img src=\'".level."inc/imgs/minus.png\' alt=\'Nicht durchgeführt\'/>');
			              $('#close_link').show();
									}
									else
									{
										$('#save_data').html('<img src=\'".level."inc/imgs/erledigt.gif\' alt=\'OK\'/>');
			              $('#exit_link').show();
									}
								}
							});
						}

          </script>
          ";
    }
    //*********************************************************************************************

    return $x;
	}

	public function confirm_request($step_id,$user_id)
	{
		$myUser = new user(clone($this->db),$user_id);
		try
		{
			$this->db->sql_query("UPDATE ccs_mut_infos SET mut_infos_datum=GETDATE(),mut_infos_status='Abgeschlossen',
	                                      mut_infos_person='$myUser->fullname', mut_infos_person_id='$myUser->id',
																				mut_infos_aktion='Ok'
		                        WHERE mut_infos_id='$step_id'");
			return null;
		}
	  catch (Exception $e)
	  {
			return $e->getMessage();
		}

	}

	/**
	 *This function accept the approval
	*/
	public function say_yes($step_id,$user_id)
	{
		$myUser = new user(clone($this->db),$user_id);
		try
		{
			$this->db->sql_query("UPDATE ccs_mut_infos SET mut_infos_datum=GETDATE(),mut_infos_status='Abgeschlossen',
	                                      mut_infos_person='$myUser->fullname', mut_infos_person_id='$myUser->id',
																				mut_infos_aktion='Ja'
		                        WHERE mut_infos_id='$step_id'");
			return null;
		}
	  catch (Exception $e)
	  {
			return $e->getMessage();
		}

	}

	public function say_no($step_id,$user_id)
	{
		$myUser = new user(clone($this->db),$user_id);
		$db = clone($this->db);
		$x = "";
		$allready_sent = array();
		if(trim($_POST['reason'])=='') { return $this->t->translate('Bitte geben Sie eine Begründung ein'); }
		try
		{
			//Write PDF File (TEST, if it's not clean an exception will throw)
			$this->write_pdf();
			$txt = str_replace("'","&#39;",$_POST['reason']);
			$this->db->sql_query("UPDATE ccs_mut_infos SET mut_infos_datum=GETDATE(),mut_infos_status='Abgeschlossen',
	                                      mut_infos_person='$myUser->fullname', mut_infos_person_id='$myUser->id',
																				mut_infos_aktion='Nein',mut_infos_begruendung='$txt'
		                        WHERE mut_infos_id='$step_id'");

			//Send reject info to request user
      $this->send_mail('ABGELEHNT',$this->request_user->id,$step_id);

			//Remove open steps
      $this->db->sql_query("DELETE FROM ccs_mut_infos WHERE mut_infos_nr='$this->mut_id' AND mut_infos_status='Neu'");

			//Inform allready involved people
			$this->db->sql_query("SELECT * FROM ccs_mut_infos WHERE mut_infos_nr='$this->mut_id' AND (mut_infos_aktion NOT LIKE 'Übersprungen' OR mut_infos_aktion is NULL) AND mut_infos_id!='$step_id'");
			while($d = $this->db->get_next_res())
			{
		    if(in_array($d['mut_infos_person_id'],$allready_sent)===FALSE)
		    {
		      $this->send_mail('ABGELEHNT',$d['mut_infos_person_id'],$step_id);
		      $allready_sent[] = $d['mut_infos_person_id'];
		    }
		    if($d['mut_infos_aktion'] == '')
		    {
		      $db->sql_query("UPDATE ccs_mut_infos SET mut_infos_aktion='Storniert', mut_infos_status='Abgeschlossen'
		                      WHERE mut_infos_id='$d[mut_infos_id]'");
		    }
			}

			//Update Workflow information
      $db->sql_query("UPDATE ccs_mutationen SET mut_status='Abgelehnt', mut_enddatum=GETDATE() WHERE mut_id='$this->mut_id'");

			$this->execute_deny_file();

			//Write PDF File (finally, overwrites the existing from above)
			$this->write_pdf();

			return null;
		}
	  catch (Exception $e)
	  {
			return $e->getMessage();
		}
	}

	public function transfer($step_id,$user_id)
	{
		$myUser = new user(clone($this->db),$user_id);
		$db = clone($this->db);
		if(trim($_POST['reason'])=='') { return $this->t->translate('Bitte geben Sie eine Begründung ein'); }
		if(trim($_POST['new_user'])=='') { return $this->t->translate('Bitte wählen Sie eine Mitarbeiter für die Übergabe aus'); }
		$newUser = new user(clone($this->db),$_POST['new_user']);
		try
		{
			$reason = str_replace("'","&#39;",$_POST['reason']);
			//Insert information for transfer
	    $db->sql_query("INSERT INTO ccs_mut_infos (mut_infos_nr,mut_infos_schritt, mut_infos_person, mut_infos_person_id, mut_infos_datum, mut_infos_herkunft,mut_infos_uebergeben_von,
																									mut_infos_typ,mut_infos_beschreibung,mut_infos_var,mut_infos_text,mut_infos_reminder,mut_infos_bedingung, mut_infos_status)
	                                	VALUES   ('$this->mut_id','$this->step_nr','".$newUser->fullname."','$newUser->id',GETDATE(),'$this->step_source','$this->step_id',
																							'$this->workflow_typ_id','$this->step_description','$this->step_var','$this->step_text','$this->step_reminder','$this->step_condition','Gesendet')");
			$new_step_id = $db->last_inserted_id;
			//Update current step
			$this->db->sql_query("UPDATE ccs_mut_infos SET mut_infos_datum=GETDATE(),mut_infos_status='Abgeschlossen',
	                                      mut_infos_person='$myUser->fullname', mut_infos_person_id='$myUser->id',
																				mut_infos_aktion='Übergeben',mut_infos_begruendung='$reason'
		                        WHERE mut_infos_id='$step_id'");

			//Send transferinfo to new responsible user
      $this->send_mail('UEBERGEBEN',$newUser->id,$new_step_id);

			return null;
		}
	  catch (Exception $e)
	  {
			return $e->getMessage();
		}
	}


	public function add_text_without_translation($txt)
	{
		$this->add_text($txt,0);
	}

	public function add_text($txt,$translation=1)
	{
		if($translation==1) { $this->html_code.= $this->t->translate($txt)."\n"; }
		else { $this->html_code.= $txt."\n"; }
	}

	public function add_field($col,$colspan=1,$td=true,$translation=false,$special_cmd=null)
	{
		$txt_description ="";
		$txt_field="";
		$d = null;
		$ext = null;
		$style = null;

    if($col->get_edit_typ()=='not_editable' OR $col->get_disabled()) { $ext = "disabled='true' class='disabled'"; }
    if($col->get_hidden()) { $style = "display:none;"; }
    if($col->get_selection())
    {
      $selection = $col->get_selection();
			if($col->get_disabled())
			{
	      foreach ($selection as $aw)
	      {
	        if($aw['value']==$col->get_default_value()) { $txt_field.= "<input type='text' disabled='true' class='disabled' style='width:".$col->get_width()*0.91."px;' name='".$col->get_save_column()."' id='".$col->get_save_column()."' ".$col->get_javascript()." value='".$aw['display']."'>"; break; }
	      }
			}
			else
			{
	      $txt_field.= "<select style='width:".$col->get_width()*0.91."px;' name='".$col->get_save_column()."' id='".$col->get_save_column()."' ".$col->get_javascript().">";
	      foreach ($selection as $aw)
	      {
	        $txt_field.= "<option";
	        if($aw['value']==$col->get_default_value()) { $txt_field.= " selected"; }
	        if($translation) { $txt_field.= " value='$aw[value]'>".$this->t->translate($aw['display'])."</option>"; } else { $txt_field.= " value='$aw[value]'>$aw[display]</option>"; }
	      }
	      $txt_field.= "</select>";
			}
    }
    else
    {
	    switch($col->get_edit_typ())
	    {
	      case 'checkbox':
	        if($col->get_default_value()==1 OR $col->get_default_value()=='on') { $val = "checked='checked'"; } else { $val = ''; }
	        $txt_field.= "<input type='checkbox' ".$val." name='".$col->db_col_name."' id='".$col->db_col_name."' style='width:".$col->get_width()."px;' ".$col->get_javascript()." ".$ext."/>";
	        break;
	      case 'radio':
	        if($col->get_default_value()==$col->col_name) { $val = "checked='checked'"; } else { $val = ''; }
	        $txt_field.= "<input type='radio' ".$val." value='".$col->col_name."' name='".$col->db_col_name."' id='".$col->db_col_name."_".$col->col_name."' style='width:".$col->get_width()."px;' ".$col->get_javascript()." ".$ext."/>";
	        break;
	      case 'area':
	        $txt_field.= "<textarea name='".$col->db_col_name."' id='".$col->db_col_name."' style='width:".$col->get_width()*0.9."px;height:".$col->get_height()*0.9."px;' ".$col->get_javascript()." ".$ext.">".$col->get_default_value()."</textarea>";
					break;
	      case 'date':
	        $txt_field.= "<input type='text' id='".$col->get_save_column()."' name='".$col->get_save_column()."' value='".$col->get_default_value()."' style='width:".$col->get_width()*0.9."px;' onKeyPress='return false;'/>";
	        $txt_field.= "<script type='text/javascript'>
	            Calendar.setup({
	                inputField     :    '".$col->get_save_column()."',   // id of the input field
	                ifFormat       :    '%d.%m.%Y',       // format of the input field
	                showsTime      :    false,
	                timeFormat     :    '24',
	                onUpdate       :    ''
	            });
	        </script>";
	        break;
	      case 'upload':
	        $txt_field.= "<input type='file' name='".$col->db_col_name."' id='".$col->db_col_name."' value='".$col->get_default_value()."' style='width:".$col->get_width()*0.9."px;' ".$col->get_javascript()." ".$ext."/>";
					$this->is_multipart = true;
	        break;
	      default:
	        $txt_field.= "<input type='text' name='".$col->db_col_name."' id='".$col->db_col_name."' value='".$col->get_default_value()."' style='width:".$col->get_width()*0.9."px;' ".$col->get_javascript()." ".$ext."/>";
	    }
		}
		if($col->col_name!='')
		{
      if($td)
			{
				$txt_description.= "<td style='".$style."vertical-align:top;'>".$this->t->translate($col->col_name);
				if($col->get_remark()) { $txt_description.= "<br><i>".$this->t->translate($col->get_remark())."</i>"; }
        $txt_description.= "</td>";
			}
			else
			{
				$txt_description.= $this->t->translate($col->col_name);
				if($col->get_remark()) { $txt_description.= "<br><i>".$this->t->translate($col->get_remark())."</i>"; }
			}
		}
		if($td)
		{
			if($col->get_edit_typ()=='radio' OR $col->get_edit_typ()=='checkbox')
			{
        if($special_cmd=='invert')
        {
  				$this->html_code.= $txt_description."<td style='".$style."' colspan='$colspan'>".$txt_field."</td>\n";
        }
        else
        {
  				$this->html_code.= "<td style='".$style."'>".$txt_field."</td>".str_replace("<td ","<td colspan='$colspan' ",$txt_description)."\n";
        }
			}
			else
			{
				$this->html_code.= $txt_description."<td style='".$style."' colspan='$colspan'>".$txt_field."</td>\n";
			}
		}
		else
		{
			if($col->get_edit_typ()=='radio' OR $col->get_edit_typ()=='checkbox') { $this->html_code.= $txt_field.$txt_description."\n"; } else { $this->html_code.= $txt_description.$txt_field."\n"; }
		}
	}

	public function newline()
	{
		$this->html_code.= "</tr><tr>\n";
	}

	public function get_workflow_history()
	{
		if($this->workflow_typ_id=='') { throw new exception("Workflow not loaded"); }
		$txt = "";

	  $txt =$txt."<h2>Workflow</h2>";
	  $txt = $txt."<table cellspacing='0' class='wf_history' style='width:100%;'>";
	  $txt = $txt."<tr>";
	  $txt = $txt."<td class='u_input' style='width:15%;'>".$this->t->translate("Datum / Zeit")."</td>";
	  $txt = $txt."<td class='u_input' style='width:15%;'>".$this->t->translate("Verantwortlicher")."</td>";
	  $txt = $txt."<td class='u_input' style='width:25%;'>".$this->t->translate("Bezeichnung")."</td>";
	  $txt = $txt."<td class='u_input' style='width:10%;'>".$this->t->translate("Status")."</td>";
	  $txt = $txt."<td class='u_input' style='width:10%;'>".$this->t->translate("Antwort")."</td>";
	  $txt = $txt."<td class='u_input' style='width:25%;'>".$this->t->translate("Begründung")."</td>";
	  $txt = $txt."</tr>";

		$db2 = clone($this->db);
		$last_id=null;
		if($this->mut_id!='')
		{
		  $this->db->sql_query("SELECT *,CONVERT(char(30),mut_infos_datum,108) as c_zeit,CONVERT(char(30),mut_infos_datum,104) as c_datum FROM ccs_mut_infos
		                          LEFT JOIN users ON ccs_mut_infos.mut_infos_person_id=users.user_id
		                          WHERE mut_infos_nr='$this->mut_id' AND  mut_infos_var!='Info' AND mut_infos_typ IS NOT NULL AND mut_infos_uebergeben_von IS NULL
		                          ORDER BY mut_infos_schritt");

		  while ($daten2 = $this->db->get_next_res())
		  {
			  $db2->sql_query("SELECT *,CONVERT(char(30),mut_infos_datum,108) as c_zeit,CONVERT(char(30),mut_infos_datum,104) as c_datum FROM ccs_mut_infos
			                          LEFT JOIN users ON ccs_mut_infos.mut_infos_person_id=users.user_id
			                          WHERE mut_infos_nr='$this->mut_id' AND mut_infos_var!='Info' AND mut_infos_uebergeben_von IS NOT NULL
			                          ORDER BY mut_infos_schritt,mut_infos_id");
		    $txt = $txt."<tr>";
		    if($daten2['mut_infos_status']=='Neu')
		    {
		      $txt = $txt."<td class='schritte'>".$this->t->translate("Noch nicht gesendet")."</td>";
		      $txt = $txt."<td class='schritte'>".$this->t->translate("Noch nicht gesendet")."</td>";
		      $txt = $txt."<td class='schritte'>".$this->t->translate($daten2['mut_infos_beschreibung'])."</td>";
		      $txt = $txt."<td class='schritte'>-</td>";
		      $txt = $txt."<td class='schritte'>-</td>";
		      $txt = $txt."<td class='schritte'>-</td>";
	        $txt = $txt."</tr>";
		    }
		    else
		    {
		      $txt = $txt."<td class='schritte' style='width:15%;'>$daten2[c_datum] $daten2[c_zeit]</td>";
					if($daten2['mut_infos_schritt']=='99')
					{
	          $txt = $txt."<td class='schritte' colspan='2'><b>>> ".$this->t->translate("Abgebrochen durch")." ";
			      if ($daten2['mut_infos_person']!='') {  $txt = $txt."$daten2[mut_infos_person]</b></td>"; } else { $txt = $txt."$daten2[user_nachname] $daten2[user_vorname]</b></td>"; }
					}
					else
					{
						$txt = $txt."<td class='schritte' style='width:15%;'>";
			      if ($daten2['mut_infos_person']!='') {  $txt = $txt."$daten2[mut_infos_person]&nbsp;</td>"; } else { $txt = $txt."$daten2[user_nachname] $daten2[user_vorname]&nbsp;</td>"; }
			      $txt = $txt. "<td class='schritte' style='width:25%;'>".$this->t->translate($daten2['mut_infos_beschreibung'])."</td>";
					}
		      if ($daten2['mut_infos_aktion']!=NULL) { $txt = $txt. "<td class='schritte' style='width:10%;'>".$this->t->translate("Erledigt")."</td>"; } else { $txt = $txt. "<td class='schritte'>".$this->t->translate("Offen")."</td>"; }
		      If ($daten2['mut_infos_aktion']!=NULL) { $txt = $txt. "<td class='schritte' style='width:10%;'>".$this->t->translate($daten2['mut_infos_aktion'])."</td>"; } else { $txt = $txt."<td class='schritte'>?</td>"; }
		      if (trim($daten2['mut_infos_begruendung'])!='') { $txt = $txt."<td class='schritte' style='width:25%;'>".nl2br(wordwrap($this->t->translate($daten2['mut_infos_begruendung'],true),40))."</td>"; } else { $txt = $txt."<td class='schritte'>-</td>"; }
		      $txt = $txt."</tr>";
		      $laststep=$daten2['mut_infos_schritt'];

		      while ($daten_u = $db2->get_next_res())
		      {
		        if($daten_u['mut_infos_uebergeben_von']==$daten2['mut_infos_id'] OR $daten_u['mut_infos_uebergeben_von']==$last_id)
		        {
		          $txt = $txt."<tr>";
		          $txt = $txt."<td class='schritte' style='width:15%;'>$daten_u[c_datum] $daten_u[c_zeit]</td>";
		          $txt = $txt."<td class='schritte'  style='width:40%;' colspan='2'><b>>> ".$this->t->translate("Übergeben an")." ";
		          if ($daten_u['mut_infos_person']!='') {  $txt = $txt." $daten_u[mut_infos_person]&nbsp;</b></td>"; } else { $txt = $txt."$daten_u[user_nachname] $daten_u[user_vorname]&nbsp;</b></td>"; }

		          //$txt = $txt. "<td class='schritte'>$daten_u[mut_schritte_beschreibung]</td>";
		          if ($daten_u['mut_infos_aktion']!=NULL) { $txt = $txt. "<td class='schritte' style='width:10%;'>Erledigt</td>"; } else { $txt = $txt. "<td class='schritte'>".$this->t->translate("Offen")."</td>"; }
		          If ($daten_u['mut_infos_aktion']!=NULL) { $txt = $txt. "<td class='schritte' style='width:10%;'>".$this->t->translate($daten_u['mut_infos_aktion'])."</td>"; } else { $txt = $txt."<td class='schritte'>?</td>"; }
		          if (trim($daten_u['mut_infos_begruendung'])!='') { $txt = $txt."<td class='schritte' style='width:25%;'>".wordwrap(nl2br($this->t->translate($daten_u['mut_infos_begruendung'],true)))."</td>"; } else { $txt = $txt."<td class='schritte'>-</td>"; }
		          $txt = $txt."</tr>";
		          $last_id = $daten_u['mut_infos_id'];
		        }
		      }
		    }
		  }

		  $this->db->sql_query("SELECT *,CONVERT(char(30),mut_infos_datum,108) as c_zeit,CONVERT(char(30),mut_infos_datum,104) as c_datum FROM ccs_mut_infos
		                          LEFT JOIN ccs_mut_schritte ON ccs_mut_infos.mut_infos_herkunft = ccs_mut_schritte.mut_schritte_id
		                          LEFT JOIN users ON ccs_mut_schritte.mut_schritte_pers_id=users.user_id
		                          WHERE mut_infos_nr='$this->mut_id' AND mut_schritte_var!='Info' AND mut_infos_typ IS NULL AND mut_infos_uebergeben_von IS NULL
		                          ORDER BY mut_infos_schritt");

		  while ($daten2 = $this->db->get_next_res())
		  {
		    $txt = $txt."<tr>";
		    $txt = $txt."<td class='schritte'>$daten2[c_datum] $daten2[c_zeit]</td>";
		    $txt = $txt."<td class='schritte'>";
		    if ($daten2['mut_infos_person_id']!='') {  $txt = $txt."$daten2[mut_infos_person]&nbsp;</td>"; } else { $txt = $txt."$daten2[user_nachname] $daten2[user_vorname]&nbsp;</td>"; }

		    $txt = $txt. "<td class='schritte'>$daten2[mut_schritte_beschreibung]</td>";
		    if ($daten2['mut_infos_aktion']!=NULL) { $txt = $txt. "<td class='schritte'>".$this->t->translate("Erledigt")."</td>"; } else { $txt = $txt. "<td class='schritte'>".$this->t->translate("Offen")."</td>"; }
		    If ($daten2['mut_infos_aktion']!=NULL) { $txt = $txt. "<td class='schritte'>".$this->t->translate($daten2['mut_infos_aktion'])."</td>"; } else { $txt = $txt."<td class='schritte'>?</td>"; }
		    if (trim($daten2['mut_infos_begruendung'])!='') { $txt = $txt."<td class='schritte'>".wordwrap(nl2br($this->t->translate($daten2['mut_infos_begruendung'],true)))."</td>"; } else { $txt = $txt."<td class='schritte'>-</td>"; }
		    $txt = $txt."</tr>";
		    $laststep=$daten2[mut_infos_schritt];

			  $db2->sql_query("SELECT *,CONVERT(char(30),mut_infos_datum,108) as c_zeit,CONVERT(char(30),mut_infos_datum,104) as c_datum FROM ccs_mut_infos
			                          LEFT JOIN ccs_mut_schritte ON ccs_mut_infos.mut_infos_herkunft = ccs_mut_schritte.mut_schritte_id
			                          LEFT JOIN users ON ccs_mut_schritte.mut_schritte_pers_id=users.user_id
			                          WHERE mut_infos_nr='$this->mut_id' AND mut_schritte_var!='Info' AND mut_infos_typ IS NULL AND mut_infos_uebergeben_von IS NOT NULL
			                          ORDER BY mut_infos_schritt,mut_infos_id");

		    while ($daten_u = $db2->get_next_res())
		    {
		      if($daten_u['mut_infos_uebergeben_von']==$daten2['mut_infos_id'] OR $daten_u['mut_infos_uebergeben_von']==$last_id)
		      {
		        $txt = $txt."<tr>";
		        $txt = $txt."<td class='schritte'>$daten_u[c_datum] $daten_u[c_zeit]</td>";
		        $txt = $txt."<td class='schritte' colspan='2'><b>>> ".$this->t->translate("Übergeben an")." ";
		        if ($daten_u['mut_infos_person']!='') {  $txt = $txt." $daten_u[mut_infos_person]&nbsp;</b></td>"; } else { $txt = $txt."$daten_u[user_nachname] $daten_u[user_vorname]&nbsp;</b></td>"; }

		        //$txt = $txt. "<td class='schritte'>$daten_u[mut_schritte_beschreibung]</td>";
		        if ($daten_u['mut_infos_aktion']!=NULL) { $txt = $txt. "<td class='schritte'>".$this->t->translate("Erledigt")."</td>"; } else { $txt = $txt. "<td class='schritte'>".$this->t->translate("Offen")."</td>"; }
		        If ($daten_u['mut_infos_aktion']!=NULL) { $txt = $txt. "<td class='schritte'>".$this->t->translate($daten_u['mut_infos_aktion'])."</td>"; } else { $txt = $txt."<td class='schritte'>?</td>"; }
		        if (trim($daten_u['mut_infos_begruendung'])!='') { $txt = $txt."<td class='schritte'>".wordwrap(nl2br($this->t->translate($daten_u['mut_infos_begruendung'],true)))."</td>"; } else { $txt = $txt."<td class='schritte'>-</td>"; }
		        $txt = $txt."</tr>";
		        $last_id = $daten_u['mut_infos_id'];
		      }
		    }
		  }
		}
		else
		{
		  $this->db->sql_query("SELECT * FROM ccs_mut_schritte
		                          WHERE mut_schritte_typ='$this->workflow_typ_id' AND mut_schritte_var!='Info'
		                          ORDER BY mut_schritte_schritt");

		  while ($daten2 = $this->db->get_next_res())
		  {
		    $txt = $txt."<tr>";
	      $txt = $txt."<td class='schritte'>".$this->t->translate("Noch nicht gesendet")."</td>";
	      $txt = $txt."<td class='schritte'>".$this->t->translate("Noch nicht gesendet")."</td>";
	      $txt = $txt."<td class='schritte'>".$this->t->translate($daten2['mut_schritte_beschreibung'])."</td>";
	      $txt = $txt."<td class='schritte'>-</td>";
	      $txt = $txt."<td class='schritte'>-</td>";
	      $txt = $txt."<td class='schritte'>-</td>";
        $txt = $txt."</tr>";
			}
		}
	  $txt = $txt."</table>";
	  return $txt;
	}

}
?>
