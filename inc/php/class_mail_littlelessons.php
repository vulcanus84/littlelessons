<?php
require_once("class_mail.php");

class littlelessons_mailer extends PHPMailer
{
	public $db;
	public $intro;
	public $regards;
	public $title;
	public $application;

	private $body_text;
	private $t;
	private $myUser;

	function __construct($lang='german')
	{
    	include(level.'inc/db.php');
		$this->db = $db;
		$this->From = 'clahu@gmx.ch';
		$this->FromName = 'LittleLessons';
		$this->application = 'Little Lessons';
		$this->isHTML(true);
		$this->t = new translation(clone($this->db),$lang);
		$this->intro = $this->t->translate('Guten Tag');
		$this->regards = $this->t->translate('Liebe Grüsse und einen wunderschönen Tag')."<br>".$this->t->translate('Dein Little Lessons Team');
	}
	function add_recipient_by_login($login)
	{
		$this->myUser = new user();
		$this->myUser->load_user_by_login($login);
		$this->add_recipient($this->myUser->id);
	}

	function add_recipient($user_id)
	{
		$this->myUser = new user($user_id);
		$this->intro = $this->t->translate('Guten Tag'). " ".$this->myUser->firstname.' '.$this->myUser->lastname;
		$this->t->set_language($this->myUser->get_frontend_language());
		if(filter_var($this->myUser->email, FILTER_VALIDATE_EMAIL))
		{
	    $this->addAddress($this->myUser->email, $this->myUser->firstname.' '.$this->myUser->lastname);  // Add a recipient
		}
	}

	function set_from($from_name,$from_email)
	{
		$this->From = $from_email;
		$this->FromName = $from_name;
	}

	function set_application($application)
	{
		$this->application = $application;
	}

	function set_title($title)
	{
		$this->title = $title;
	}

	function get_title()
	{
		if(isset($this->title)) { return $this->title; } else { return null; }
	}


	function add_text($text)
	{
		$this->body_text.=$text;
	}

	public function get_text()
	{
		return "
							<!DOCTYPE html>\n
								<head>\n
						      <style>\n
						        body {font-family: arial; font-size:11pt;}\n
						        table.master {background-color:#F3F3F3;border-radius:20px; border: 1px solid #CCC;padding:5px; }\n
										h2 { font-size:13pt;margin-top:5px;margin-bottom:5px; }
						      </style>\n
								</head>\n
								<body>\n
						      <table class='master' style='width:100%;'>\n
						        <tr>\n
						          <td style='font-size:20pt;font-weight:bold;padding:5px;color:#444;'>".$this->application."</td>\n
						        </tr>\n
						        <tr>\n
					            <td style='font-size:12pt;padding-left:5px;padding-bottom:10px;border-bottom:1px solid #CCC;color:#444;'>".$this->title."</td>\n
					          </tr>\n
					          <tr>\n
					            <td style='padding-left:5px;'>\n
												<p>".$this->intro."</p><p>".$this->body_text."</p><p>".$this->regards."</p>\n
											</td>\n
										</tr>\n
									</table>\n
								</body>\n
							</html>\n";
	}

	function send_mail()
	{
		$this->Subject = $this->title;
		$this->Body = $this->get_text();

		if(!$this->send())
			{
				throw new exception("Message could not be sent, Mailer Error: ". $this->ErrorInfo);
			}
	}

}
