<?php
/*
  Create a HTML Page
*/

class page
{
  public $error_text;                   //Save error text and display it on get_html_code instead of content
  public $permission_required = true;   //if its true, page filename must be added to users permission
  public $login_required = true;        //If its true, user must be logged in to view the page
  public $menu;                         //HTML code for menu (created by menu class)
  public $t;                           //Pointer to translation class

  private $db;                          //Datebase pointer (will be set on construct of class)
  private $title;                       //Title of page, will be set to HTML head and displayed on top of page
  private $subtitle;                    //Subtitle of page, will be displayed on top of page
  private $filename;                    //Filename of page
  private $own_folder;                  //Foldername in which is page
  private $path;                        //Full path to page filename
  private $space="      ";              //used for better looking html source code
  private $content;                     //save the text until it will be printed by get_html_code
  private $arr_header_lines = array();  //array with all header lines (filled by the functions add_header_line, add_css_link, add_js_link)
  private $logger;

  public function __construct()
  {
    //Get database connection
    include(level.'inc/db.php');
    $this->db = $db;
    $this->logger = new log();

    //Used for adding/removing parameters to URL
    $page = new header_mod();

    //Get filename
    $this->filename = basename($_SERVER["REQUEST_URI"]);
    if(strpos($this->filename,".")==0) { $this->filename = "index.php"; }
    if(strpos($this->filename,"?")!==FALSE) { $this->filename = substr($this->filename,0,strpos($this->filename,"?")); }

    //Get file path
    $this->path = str_replace($_SERVER['DOCUMENT_ROOT'],"",$_SERVER['SCRIPT_FILENAME']);
    $this->path = substr($this->path,1);

    //Get folder path
    $this->own_folder = substr($this->path,0,strrpos($this->path,"/"));
    if(strpos($this->own_folder,"/")!=0) { $this->own_folder = substr($this->own_folder,strrpos($this->own_folder,"/")+1); }

    //Set standard headers for all files
		$this->add_header_line("<meta http-equiv='X-UA-Compatible' content='IE=edge'>");
    $this->add_header_line("<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>");

    $this->add_header_line("<link rel='apple-touch-icon' sizes='180x180' href='".level."inc/imgs/favicon/apple-icon-180x180.png'>");
		$this->add_header_line("<link rel='icon' type='image/png' sizes='192x192'  href='".level."inc/imgs/favicon/android-icon-192x192.png'>");
		$this->add_header_line("<link rel='icon' type='image/png' sizes='96x96' href='".level."inc/imgs/favicon/favicon-96x96.png'>");
		$this->add_header_line("<meta name='msapplication-TileImage' content='".level."inc/imgs/favicon/ms-icon-144x144.png'>");
    $this->add_header_line("<meta name='theme-color' content='#ffffff'>");

		$this->add_header_line("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no\"/>");

    //Set standard style sheets for all files
    //$this->add_css_link(level."inc/bootstrap-5-1-3/css/bootstrap.min.css");
    $this->add_css_link(level."inc/css/main.min.css");
    $this->add_css_link(level."inc/css/styles.css");
    $this->add_js_link(level."inc/js/jquery-3.5.1.min.js");
    $this->add_js_link(level."inc/js/jquery-1.11.3-ui.min.js");

    //Set standard javascript includes for all files
    $this->add_js_link(level."inc/bootstrap-5-1-3/js/bootstrap.bundle.min.js");


		try
		{
	    if(isset($_GET['change_language']))
			{
				$_SESSION['login_user']->set_frontend_language($_GET['change_language']);
        $_SESSION['login_user']->save();
				$page->remove_parameter('change_language');
				header("Location: ".$page->get_link());
			}

	    if(isset($_SESSION['login_user']))
			{
				$this->t = new translation(clone($db),$_SESSION['login_user']->get_frontend_language());
			}
			else
			{
				$this->t = new translation($db,"german");
			}

	    if(isset($_POST['user_login'])) { $this->check_login($_POST['user_login'],$_POST['pw']); }
	    if(isset($_GET['user_login'])) { $this->check_login($_GET['user_login'],$_GET['pw']); }
	    if(isset($_GET['action']) && $_GET['action']=='logout') { $this->logout(); }
	    if(isset($_GET['action']) && $_GET['action']=='login') { $this->login(); }
		}
	  catch (Exception $e)
	  {
	    $this->error_text = $e->getMessage();
			$this->t = new translation($db,"german");
	  }
  }

	public function get_setting($setting_name)
	{
		switch($setting_name)
		{
			case 'sql_user_selection':
				return "SELECT *, CONCAT(user_lastname,' ',user_firstname) as user_fullname
								FROM users
								ORDER BY user_lastname, user_firstname";
		}
	}

  /**
   * Show standardized info text
  */
	public function show_info($text)
	{
	  $txt = "<div style='border:1px solid gray;border-radius:5px;padding:5px;'>";
    $txt.= "<table width='100%;'><tr>";
    $txt.= "<td style='padding-right:10px;width:50px;'><img style='height:30px;' src='".level."inc/imgs/info.png' alt='Information'/></td>";
	  $txt.= "<td>".$text."</td>";
	  $txt.= "</tr></table></div>";
		return $txt;
	}

  public function show_error($text)
	{
	  $txt = "<div class='alert alert-danger' role='alert'>";
	  $txt.= $text;
	  $txt.= "</div>";
		$this->add_content($txt);
	}

  //Add content to the page
  public function add_content_with_translation($txt)
	{
		$this->add_content($this->t->translate($txt));
	}

  //Add content to the page
  public function add_content($txt)
  {
    if(strpos($txt,"\n")===FALSE)
    {
      if(substr($this->content,strlen($this->content)-strlen($this->space))==$this->space)
      {
        $txt = $txt."\n";
      }
      else
      {
        $txt = $this->space.$txt."\n";
      }
    }
    $this->content.= $txt;
  }


	/**
	 *Returns the HTML code from the page class
	 *
	 *Possible Modus:
	 *- full (standard) -> whole HTML Code with all CCS elements
	 *- only_html -> all HTML elements without header and footer from CCS
	 *- only_content -> only added content without HTML (usefull to get content over AJAX)
	 */
  public function get_html_code($modus='full')
  {
    try
    {
      if($this->get_title() OR $this->get_subtitle())
      {
        if($this->get_subtitle()) { $this->arr_header_lines[] = "<title>".$this->get_title()." > ".$this->get_subtitle()."</title>"; } else { $this->arr_header_lines[] = "<title>".$this->get_title()."</title>"; }
      }
			$txt = "";
      if($modus!='only_content')
			{
				$txt.= "<!DOCTYPE html>\n";
	      $txt.= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
	      $txt.= "  <head>\n";
	      //include special headerlines
	      foreach($this->arr_header_lines as $akt_header_line)
	      {
	        $txt .= "    ".$akt_header_line."\n";
	      }

	      $txt.= "  </head>\n";
	      $txt.= "  <body>\n";
			}
			if($modus=='full')
			{
	      $txt.= "    <div class='page'>\n";
      	$txt.= $this->get_header();
      }
      //If a error occured show the error and no content
      if(isset($this->error_text))
      {
        $txt.= "     <div class='container-lg my-4'>\n";
        $txt.= "        <div class='alert alert-danger' role='alert'>\n";
        $txt.= $this->error_text;
        $txt.= "        </div>\n";
        $txt.= "      </div><!--End Content-->\n";
      }
      else
      {
        if($this->login_required===false)
        {
          if(isset($this->menu)) { $txt.= $this->get_menu(); }
          $txt.= $this->get_content();
        }
        else
        {
          if(isset($_SESSION['login_user']))
          {
            if($this->permission_required===false)
            {
              if(isset($this->menu)) { $txt.= $this->get_menu(); }
              $txt.= $this->get_content();
            }
            else
            {
              if($_SESSION['login_user']->check_permission($this->path))
              {
                if(isset($this->menu)) { $txt.= $this->get_menu(); }
                $txt.= $this->get_content();
              }
              else
              {
                $txt.= $this->get_login_mask();
              }
            }
          }
          else
          {
            $txt.= $this->get_login_mask();
          }
        }
      }
      if($modus!='only_content')
			{
				if($modus=='full')
				{
					$txt.= $this->get_footer();
	      	$txt.= "    </div><!--End Page-->\n";
				}
        $txt.= "  </body>\n";
	      $txt.= "</html>";
			}
      return $txt;
    }
    catch (Exception $e)
    {
      return $e->getMessage();
    }
  }

  public function add_css_link($css)
  {
    $this->arr_header_lines[] = "<link rel='stylesheet' href='".$css."' type='text/css'/>";
  }

  public function add_css($css)
  {
    $arr_css = explode("\n",$css);
    $this->arr_header_lines[] = "<style type='text/css'>";
    foreach($arr_css as $css)
    {
      $this->arr_header_lines[] = "  ".$css;
    }
    $this->arr_header_lines[] = "</style>";
  }

  public function add_js_link($js)
  {
    $this->arr_header_lines[] = "<script type='text/javascript' src='".$js."'></script>";
  }

  public function add_js($js)
  {
    $arr_js = explode("\n",$js);
    $this->arr_header_lines[] = "<script type='text/javascript'>";
    foreach($arr_js as $js)
    {
      $this->arr_header_lines[] = "  ".$js;
    }
    $this->arr_header_lines[] = "</script>";
  }

  public function add_header_line($header_line)
  {
    $this->arr_header_lines[] = $header_line;
  }

  private function get_header()
  {
    $txt = "";
    $txt.= "
          <div class='container-lg'>
            <nav class='navbar navbar-expand-lg navbar-light border-bottom'>
              <a class='navbar-brand' href='".level."index.php'><span class='display-5'>Little Lessons</span></a>
              <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarSupportedContent' aria-controls='navbarSupportedContent' aria-expanded='false' aria-label='Toggle navigation'>
                <span class='navbar-toggler-icon'></span>
              </button>
              <div class='collapse navbar-collapse' id='navbarSupportedContent'>
                <ul class='navbar-nav me-auto mb-2 mb-lg-0'>
            
                ";
if(!isset($_SESSION['login_user']))
{
  $txt.= "
                  <li class='nav-item'>
                    <a class='nav-link' href='".level."app_user_admin/register.php'>Registrieren</a>
                  </li>
                  <li class='nav-item'>
                    <a class='nav-link' href='".level."app_user_admin/my_user.php'>Login</a>
                  </li>
  ";
}
else
{
  $txt.= "
                  <li class='nav-item'>
                    <a class='nav-link' href='".level."app_user_admin/my_user.php'>Mein Profil</a>
                  </li>
                  <li class='nav-item'>
                    <a class='nav-link' href='".level."app_course_admin/my_courses.php'>Mein Kurse</a>
                  </li>
                  <li class='nav-item'>
                    <a class='nav-link' href='?action=logout'>Logout</a>
                  </li>
  ";
} 
  $txt.= "
  </ul>
              </div>
            </nav>
          </div>
          ";
      return $txt;
  }

  private function get_menu()
  {
    $txt = "      <div class='menu'>\n";
    $txt.= $this->menu;
    $txt.= "      </div><!--End Menu-->\n";
    return $txt;
  }

  private function get_content()
  {
    $txt =  "     <div class='container-lg my-4'>\n";
    $txt.= $this->content;
    $txt.= "      </div><!--End Content-->\n";
    return $txt;
  }

  private function get_footer()
  {
    $txt = "      <div class='container-lg'>\n";
    $txt.= "        <table style='width:100%;' >\n";
    $txt.= "          <tr>\n";
    $txt.= "            <td colspan='3'><hr/></td>\n";
    $txt.= "          </tr>\n";
    $txt.= "          <tr>\n";
    $txt.= "            <td style='width:33%;text-align:left;font-size:8pt;'>";
    if(isset($_SESSION['login_user'])){ $txt.= $_SESSION['login_user']->fullname; }
    $txt.= "</td>\n";
    $txt.= "            <td style='width:33%;text-align:center;font-size:8pt;'>".date('d.m.Y')."</td>\n";
    $txt.= "            <td style='width:33%;text-align:right;font-size:8pt;'></td>\n";
    $txt.= "          </tr>\n";
    $txt.= "        </table>\n";
    $txt.= "      </div><!--End Footer-->\n";
    return $txt;
  }

  public function get_login_mask()
  {
  	$txt = "";
    $txt.= "
    <div class='container-lg'>
      <form id='login' action='' method='POST' name='login'>
          <div class='form-floating my-3'>
            <input type='text' class='form-control' id='user_login' name='user_login'>
            <label for='user_login' class='form-label'>Benutzer</label>
          </div>
          <div class='form-floating my-3'>
            <input type='password' class='form-control' id='pw' name='pw'>
            <label for='pw' class='form-label'>Passwort</label>
          </div>
          <button onclick='this.submit();' type='submit' class='btn btn-primary'>Anmelden</button>
        </form>
      </div>
      ";
	  return $txt;

  }

  private function logout()
  {
		$_SESSION['login_user']->save();
    $this->logger->write_to_log('User','Logout');
    session_destroy();
    header("Location: ".level."index.php");
    die();
  }

  private function login()
  {
    header("Location: index.php");
    die();
  }

  private function check_login($user_login,$pw)
  {
    $page = new header_mod();                               //about the current page and header modification functions
    $result = $this->db->sql_query("SELECT * FROM users
                           WHERE user_account = :user_account OR user_email = :user_account",array('user_account'=>$user_login));
    $data = $this->db->get_next_res();
    if ($this->db->count()==0)
    {
      if(trim($user_login)!='')
      {
   			$this->logger->write_to_log('User','Try to login with unknown user '.$user_login);
        $this->error_text  = "Der angegeben User existiert nicht in der Datenbank.<br> Bitte überprüfen Sie ihr Kurzzeichen";
      }
    }
    else
    {
      if (hash('sha256', $pw)==$data->user_password)
      {
        if($data->user_status=='Registred')
        {
          $this->logger->write_to_log('User','Try to login with not verified email for user '.$user_login);
          $myUser = new user($data->user_id);
          $myUser->send_verification_email();
          $this->error_text = "Deine E-Mail Adresse wurde noch nicht bestätigt. <br/>Ich habe die die Aktivierungs E-Mail soeben nochmals gesendet.";
          $page->remove_parameter('action');
          $page->remove_parameter('user');
        }
        else
        {
          $_SESSION['login_user'] = new user($data->user_id);
          $page->remove_parameter('logout');
          $page->remove_parameter('user_login');
          $page->remove_parameter('user_id');
          $page->remove_parameter('pw');
          $page->remove_parameter('x');
           $this->logger->write_to_log('User','Login');
          header("Location: ".$page->get_link());
        }
      }
      else
      {
   			$this->logger->write_to_log('User','Try to login with wrong password for user '.$user_login);
        $this->error_text = "Falsches Passwort";
        $page->remove_parameter('action');
        $page->remove_parameter('user');
      }
    }

  }

  public function set_info($info)
  {
    $this->info = $info;
  }

  public function get_info()
  {
    if(isset($this->info)) { return $this->info; } else { return null; }
  }

  public function set_title($title)
  {
    $this->title = $title;
  }

  public function get_title()
  {
    if(isset($this->title)) { return $this->title; } else { return null; }
  }

  public function set_subtitle($title)
  {
    $this->sub_title = $title;
  }

  public function get_subtitle()
  {
    if(isset($this->sub_title)) { return $this->sub_title; } else { return null; }
  }

  public function get_path()
  {
    if(isset($this->path)) { return $this->path; } else { return null; }
  }

}
?>
