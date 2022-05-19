<?php
require_once("class_mail_littlelessons.php");

class user
{
	public $firstname;
	public $lastname;
	public $fullname;
	public $email;
	public $gender;
	public $image_path;
	public $status;

	private $frontend_language;
	private $db;

	//Read-only konfiguriert
	protected $id;
	protected $login;

	public function __construct($user_id=0)
	{
    	include(level.'inc/db.php');
    	if($user_id!=0) { $this->load_user_by_id($user_id); }
	}

	public function __get($name)
	{
		if (isset($this->$name)) { return $this->$name; } else { return null;  }
	}

  	public function __set($name, $value)
  	{
    	if ($name === 'login' OR $name === 'id') { throw new Exception("Error in class User: <br>Not allowed to change property $name"); }
    	else { $this->$name = $value; }
	}

	public function set_firstname($firstname)
	{
		$this->firstname = $firstname;
	}
	public function set_lastname($lastname)
	{
		$this->lastname = $lastname;
	}
	public function set_email($email)
	{
		$this->email = $email;
	}
	public function set_gender($gender)
	{
		$this->gender = $gender;
	}

	function set_status($status)
	{
		$this->status = $status;
	}

	public function save()
	{
		include(level.'inc/db.php');
		if($this->id==0)
		{
			$myGUID = $this->create_guid();

			$db->insert(array('user_firstname'=>$this->firstname,
								'user_lastname'=>$this->lastname,
								'user_account'=>$this->email,
								'user_email'=>$this->email,
								'user_gender'=>$this->gender,
								'user_language' => $this->get_frontend_language(),
								'user_verification_code' => $myGUID
							),'users');
			$this->load_user_by_id($db->last_inserted_id);
			$myMail = new littlelessons_mailer();
			$myMail->add_recipient($this->id);
			$myMail->set_title("E-Mail Bestätigung");
			$myMail->add_text("bitte bestätigten sie die E-Mail Adresse mit dem folgenden Link:");
			$myMail->add_text("<a href='https://www.clanic.ch/littlelessons/app_user_admin/verify.php?verification_code=".$myGUID."'>https://www.clanic.ch/littlelessons/app_user_admin/verify.php?verification_code=".$myGUID."</a>");
			$myMail->send_mail();
		}
		else
		{
			$db->update(array('user_firstname'=>$this->firstname,
								'user_lastname'=>$this->lastname,
								'user_account'=>$this->email,
								'user_email'=>$this->email,
								'user_gender'=>$this->gender,
								'user_status'=>$this->status,
								'user_language' => $this->get_frontend_language()
							),'users','user_id',$this->id);
		}
  	}

 	public function load_user_by_login($login)
  	{
		include(level.'inc/db.php');
		$res = $db->sql_query_with_fetch("SELECT * FROM users WHERE user_account=:uid",array('uid'=>$login));
		$this->load_user_by_id($res->user_id);
  	}

  	
	public function reload()
  	{
		$this->load_user_by_id($this->id);
	}

  	public function load_user_by_id($id)
  	{
    	include(level.'inc/db.php');
    	$db->sql_query("SELECT * FROM users	WHERE user_id=:uid",array('uid'=>$id));
    	if($db->count()>0)
    	{
			$res = $db->get_next_res();
			$this->id = $id;
			$this->login = $res->user_account;

			$this->firstname = $res->user_firstname;
			$this->lastname = $res->user_lastname;
			$this->status = $res->user_status;
			$this->email = $res->user_email;
			$this->fullname = $this->firstname." ".$this->lastname;
			if(trim($this->fullname)=='') { $this->fullname = $this->login; }
			$this->gender = $res->user_gender;

			$this->set_frontend_language($res->user_language);
			$this->image_path = "app_user_admin/pics/".$this->login.".jpg";
    	}
    	else
		{
			throw new Exception("Error in class User: <br>User with ID $id not found");
		}
 	}

  	public function check_permission($path,$permission_typ='read')
  	{
		include(level.'inc/db.php');

		//evaluate normal permissions
		$db->sql_query("SELECT * FROM permissions WHERE permission_user_id='$this->id'");
		while($d = $db->get_next_res())
		{
			switch($permission_typ)
			{
				case 'read':
				if(strpos($path,$d->permission_path)!==FALSE AND $d->permission_read=='1') { return true; }
				break;
				case 'write':
				if(strpos($path,$d->permission_path)!==FALSE AND $d->permission_write=='1') { return true; }
				break;
				case 'delete':
				if(strpos($path,$d->permission_path)!==FALSE AND $d->permission_delete=='1') { return true; }
				break;
				case 'app_permission':
				if(strpos($d->permission_path,$path)!==FALSE AND ($d->permission_read=='1' OR $d->permission_write=='1' OR $d->permission_delete=='1')) { return true; }
				break;
			}
    	}
  	}

	public function set_frontend_language($language)
	{
		switch($language)
		{
			case 'german':
				$this->frontend_language = 'german';
				break;
			case 'english':
				$this->frontend_language = 'english';
				break;
			default:
				$this->frontend_language = 'german';
				break;
		}
	}

	public function get_frontend_language()
	{
    	if(isset($this->frontend_language)) { return $this->frontend_language; } else { return null; }
	}


	function get_picture($with_name=true,$javascript_function=null,$size='',$thumbnail=false)
	{
		$css='';
		$pic_path = $this->get_pic_path($thumbnail);

		if($size!='') { $css = 'width:'.$size.';'; }
		if($with_name)
		{
			$js = null;
			if($javascript_function)
			{
				$js = " onclick='".$javascript_function."(".$this->id.");'";
				$css.= "cursor:pointer;";
			}
			if($this->hidden) { $css.= "opacity:0.3"; }
			$x = "<div class='user_mit_name' id='user".$this->id."'>";
			$x.= "<img alt='$this->login' title='$this->login' style='$css' class='user' src='$pic_path' $js/>";
			$x.= "<br/>".$this->login."</div>";
			return $x;
		}
		else
		{
			$js = null;
			if($javascript_function)
			{
				$js = " onclick='".$javascript_function."(".$this->id.");'";
				$css.= "cursor:pointer;";
			}
			if($css!='') { $css = "style='".$css."'"; }
			return "<img alt='$this->login' title='$this->login' $css class='user' src='$pic_path' $js/>";
		}
	}

	function get_ori_pic_path($thumbnail=false)
	{
		if($thumbnail)
		{
			$pic_path = level."app_user_admin/user_pics/".$this->id."_t.png";
		}
		else
		{
			$pic_path = level."app_user_admin/user_pics/".$this->id.".png";
		}
		if(!file_exists($pic_path))
		{
			if($this->gender=='Herr') { $pic_path = level.'inc/imgs/default_man.png'; } else { $pic_path = level.'inc/imgs/default_woman.png'; }
		}

		return $pic_path;
	}

	function get_pic_path($thumbnail=false)
	{
		if($thumbnail)
		{
			$pic_path = level."app_user_admin/user_pics/".$this->id."_t.png";
		}
		else
		{
			$pic_path = level."app_user_admin/user_pics/".$this->id.".png";
		}

		if(!file_exists($pic_path))
		{
			if($this->gender=='Herr') { $pic_path = level.'inc/imgs/default_man.png'; } else { $pic_path = level.'inc/imgs/default_woman.png'; }
		}

		return $pic_path;
	}

	function check_password($pw)
	{
		include(level.'inc/db.php');
		$db->sql_query("SELECT * FROM users
						WHERE user_account = :user_account",array('user_account'=>$this->login));
		$daten = $db->get_next_res();
		if ($db->count()==1)
		{
			if (hash('sha256', $pw)==$daten->user_password) { return true; } else { return false; }
		}
	}

	function set_password($pw)
	{
		include(level.'inc/db.php');
		$db->update(array('user_password'=>hash('sha256', $pw)),'users','user_id',$this->id);
	}

	function update_password($old_pw,$new_pw)
	{
		include(level.'inc/db.php');
		if($this->check_password($old_pw))
		{
			$db->update(array('user_password'=>hash('sha256', $new_pw)),'users','user_id',$this->id);
		}
		else 
		{
			throw new Exception("Altes Passwort falsch");
		}
	}

	// Create GUID (Globally Unique Identifier)
	function create_guid() 
	{ 
		$guid = '';
		$namespace = rand(11111, 99999);
		$uid = uniqid('', true);
		$data = $namespace;
		$data .= $_SERVER['REQUEST_TIME'];
		$data .= $_SERVER['HTTP_USER_AGENT'];
		$data .= $_SERVER['REMOTE_ADDR'];
		$data .= $_SERVER['REMOTE_PORT'];
		$hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
		$guid = substr($hash, 0, 8) . '-' .
				substr($hash, 8, 4) . '-' .
				substr($hash, 12, 4) . '-' .
				substr($hash, 16, 4) . '-' .
				substr($hash, 20, 12);
		return $guid;
	}

}

?>
