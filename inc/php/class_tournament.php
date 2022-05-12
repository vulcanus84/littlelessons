<?php
class tournament
{
	public $id;
	private $db;
	private $tournament_title;
	private $tournament_description;
	private $number_of_courts;
	private $number_of_players;
	private $number_of_seedings;
	private $tournament_system;
	private $tournament_counting;
	private $tournament_status;
	private $tournament_rounds;

	function __construct($db,$tournament_id=null)
	{
		$this->id=$tournament_id;
		$this->db=$db;
		if($tournament_id!=null)
		{
	    $this->db->sql_query("SELECT * FROM groups WHERE group_id= :tournament_id",array('tournament_id'=>$tournament_id));
	    if($this->db->count()==1)
	    {
        $data = $this->db->get_next_res();
  	    $this->tournament_title = $data->group_title;
  	    $this->tournament_description = $data->group_description;
  	    $this->number_of_courts = $data->group_courts;
  	    $this->tournament_system = $data->group_system;
  	    $this->tournament_counting = $data->group_counting;
  	    $this->tournament_status = $data->group_status;
		    $this->db->sql_query("SELECT * FROM games WHERE game_group_id= :tournament_id ORDER BY game_round DESC",array('tournament_id'=>$tournament_id));
		    if($this->db->count()>0)
		    {
			    $data = $this->db->get_next_res();
	  	    $this->tournament_rounds = $data->game_round;
		    }
		    else
		    {
	  	    $this->tournament_rounds = 0;
		    }
		    $this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id= :tournament_id",array('tournament_id'=>$tournament_id));
  	    $this->number_of_players = $this->db->count();
				$this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id= :tournament_id AND group2user_seeded IS NOT NULL",array('tournament_id'=>$tournament_id));
  	    $this->number_of_seedings = $this->db->count();
      }
      else
      {
        throw new Exception("Tournament with the following ID not found: ".$tournament_id);
      }
    }
	}

	function get_number_of_seedings()
	{
    return $this->number_of_seedings;
  }

	function get_title()
	{
    return $this->tournament_title;
  }

	function get_description()
	{
    return $this->tournament_description;
  }

  function get_number_of_courts()
	{
    return $this->number_of_courts;
  }

  function get_number_of_players()
	{
    return $this->number_of_players;
  }

	function get_system()
	{
    return $this->tournament_system;
  }

	function get_counting()
	{
    return $this->tournament_counting;
  }

	function get_status()
	{
    return $this->tournament_status;
  }

	function get_rounds()
	{
    return $this->tournament_rounds;
  }

	function get_users_for_seedings()
  {
  	$x = "";
  	$arr_users = array();
		$x.= "<h1>Teilnehmer</h1>";
  	$this->db->sql_query("SELECT * FROM group2user
  												LEFT JOIN users ON group2user_user_id = user_id
  												WHERE group2user_group_id='".$this->id."' AND group2user_seeded = 99
  												ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC");
  	$first = true;
  	while($d = $this->db->get_next_res())
  	{
  		$my_user = new user($d->group2user_user_id);
  		$x.= "<div class='user_mit_BHZ' onclick='add_as_seeded($my_user->id);'>".$my_user->get_picture(false,null,'80px',true)."<br/>".$my_user->login."</div>";
  		$my_user = null;
  	}
  	return $x;
  }


  function get_users_for_teams()
  {
  	$x = "";
  	$arr_users = array();
		$x.= "<h1>Teilnehmer</h1>";
		for($i=1;$i <= $this->get_number_of_players()/2;$i++)
		{
			$w_str = "WHERE group2user_group_id='".$this->id."'";
			foreach($arr_users as $u)
			{
				$w_str.= " AND group2user_user_id!='$u'";
			}
			$data = $this->db->sql_query_with_fetch("SELECT * FROM group2user LEFT JOIN users ON group2user_user_id=users.user_id $w_str ORDER BY group2user_partner_id DESC, user_account LIMIT 1");
			$myUser = new user($data->user_id);
			$arr_users[] = $data->user_id;
			if($data->group2user_partner_id!='')
			{
				$myUser = new user($data->group2user_partner_id);
				$arr_users[] = $data->group2user_partner_id;
			}
			else
			{
				break;
			}
		}
		$w_str = "WHERE group2user_group_id='".$this->id."'";
		foreach($arr_users as $u)
		{
			$w_str.= " AND group2user_user_id!='$u'";
		}
  	$this->db->sql_query("SELECT * FROM group2user
  												LEFT JOIN users ON group2user_user_id = user_id
  												$w_str
  												ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC");
  	$first = true;
  	while($d = $this->db->get_next_res())
  	{
  		$my_user = new user($d->group2user_user_id);
  		$x.= "<div class='user_mit_BHZ' onclick='add_as_partner($my_user->id);'>".$my_user->get_picture(false,null,'80px',true)."<br/>".$my_user->login."</div>";
  		$my_user = null;
  	}
  	return $x;
  }



  function get_users_from_tournament($mode='default')
  {
  	$x = "";
  	if($mode!='narrow')
  	{
 			$x.= "<h1>Teilnehmer</h1>";
 			if(!isset($_GET['round']))
 			{
 				$x.="<a href='match_pdf.php?tournament_id=".$this->id."' target='_blank'>Alle Matchblätter</a>";
 			}
 			else
 			{
 				$x.="<a href='match_pdf.php?tournament_id=".$this->id."&round=".$_GET['round']."' target='_blank'>Matchblätter Runde ".$_GET['round']."</a>";
 			}
  	}

  	$last_wins = "";
  	$this->db->sql_query("SELECT * FROM group2user
  												LEFT JOIN users ON group2user_user_id = user_id
  												WHERE group2user_group_id = '$this->id'
  												ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC");
  	$first = true;
  	$arr_displayed = array();

  	while($d = $this->db->get_next_res())
  	{
  		if($last_wins!=$d->group2user_wins)
  		{
  			if($mode=='narrow')
  			{
	  			if(!$first) { $x.= "</div>"; }
	  			if($d->group2user_wins<>1)
	  			{
	  				$x.= "<div class='siege' style='border-right:0px solid #DDD;margin-right:10px;padding-right:10px;float:left;'>".$d->group2user_wins.' Siege<p>';
	  			}
	  			else
	  			{
	  				$x.= "<div class='siege' style='border-right:0px solid #DDD;margin-right:10px;padding-right:10px;float:left;'>".$d->group2user_wins.' Sieg<p>';
	  			}
	  			$first = false;
  			}
	  		else
	  		{
					if($d->group2user_wins<>1)
					{
						$x.= "<hr style='clear:both;'/><div class='siege'>".$d->group2user_wins.' Siege</div>';
					}
					else
					{
						$x.= "<hr style='clear:both;'/><div class='siege'>".$d->group2user_wins.' Sieg</div>';
					}
	  		}
  		}

  		$my_user = new user($d->group2user_user_id);
  		if($this->get_system()=='Schoch' OR $this->get_system()=='Doppel_dynamisch')
  		{
	  		$x.= "<div class='user_mit_BHZ' onclick='show_user_games($my_user->id);'>".$my_user->get_picture(false,null,'80px',true)."<br/>".$my_user->login."<br/>".$d->group2user_BHZ.".".$d->group2user_FBHZ."</div>";
  		}
  		else
  		{
	  		if($this->get_system()=='Doppel_fix')
	  		{
	  			if(!in_array($my_user->id,$arr_displayed))
	  			{
		  			$db2 = clone($this->db);
		  			$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='".$this->id."' AND group2user_user_id='$my_user->id'");
		  			$partner = new user($d2->group2user_partner_id);
			  		$x.= "<div class='user_mit_BHZ' onclick='show_user_games($my_user->id);'>".$my_user->get_picture(false,null,'80px',true).$partner->get_picture(false,null,'80px',true)."<br/>".$my_user->login." & ".$partner->login."<br/>".$d->group2user_BHZ.".".$d->group2user_FBHZ."</div>";
			  		$arr_displayed[] = $my_user->id;
			  		$arr_displayed[] = $partner->id;
	  			}
	  		}
	  		else
	  		{
		  		$x.= "<div class='user_mit_BHZ' onclick='show_user_games($my_user->id);'>".$my_user->get_picture(false,null,'80px',true)."<br/>".$my_user->login."<br/>".$d->group2user_BHZ.".".$d->group2user_FBHZ."</div>";
	  		}
  		}
  		$my_user = null;
  		$last_wins = $d->group2user_wins;
  	}
  	if($mode=='narrow') { $x.= "</div>"; }
  	return $x;
  }

  function get_all_users($js_event_name,$group_by='alphabetical')
	{
		if(isset($_GET['show_hidden']) AND $_GET['show_hidden']=='1')
		{
			$w_str = "WHERE user_id!='1'";
		}
		else
		{
			$w_str = "WHERE user_id!='1' AND user_hide<1";
		}

		//Check permissions
		$this->db->sql_query("SELECT * FROM location_permissions
													LEFT JOIN locations ON loc_permission_loc_id = location_id
													WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'");
		if($this->db->count()==0) { $w_str.= " AND user_id=0"; } else { $w_str.= " AND ("; }
		$i=0;
		while($d = $this->db->get_next_res())
		{
			if($i==0) { $w_str.= "user_training_location='$d->location_id'"; } else { $w_str.= " OR user_training_location='$d->location_id'"; }
			$i++;
		}
		if($this->db->count()>0) { $w_str.= ")"; }

		if($this->id!=null)
		{
			$this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id = '$this->id'");
			while($data = $this->db->get_next_res())
			{
				$w_str.= " AND user_id!='".$data->group2user_user_id."'";
			}
		}

		$x = "";
		if($group_by=='alphabetical')
		{
			$this->db->sql_query("SELECT * FROM users $w_str ORDER BY user_account ASC");
			$x.= "<h1>Spieler (".$this->db->count().")</h1>";
			while($data = $this->db->get_next_res())
			{
				$my_user = new user($data->user_id);
				$x.= $my_user->get_picture(true,$js_event_name,null,true);
				$my_user = null;
			}
		}

		if($group_by=='gender')
		{
			$this->db->sql_query("SELECT * FROM users $w_str AND user_gender='Frau' ORDER BY user_account ASC");
			$x.= "<h1>Mädchen (".$this->db->count().")</h1>";
			while($data = $this->db->get_next_res())
			{
				$my_user = new user($data->user_id);
				$x.= $my_user->get_picture(true,$js_event_name,null,true);
				$my_user = null;
			}
			$x.="<div style='clear:both;border-bottom:1px solid gray;'>&nbsp;</div>";
			$this->db->sql_query("SELECT * FROM users  $w_str AND user_gender='Herr' ORDER BY user_account ASC");
			$x.= "<h1>Jungs (".$this->db->count().")</h1>";
			while($data = $this->db->get_next_res())
			{
				$my_user = new user($data->user_id);
				$x.= $my_user->get_picture(true,$js_event_name,null,true);
				$my_user = null;
			}
		}

		if($group_by=='location')
		{
			$this->db2 = clone($this->db);
			$this->db2->sql_query("SELECT MAX(location_name) as location_name, MAX(user_training_location) as user_training_location
														FROM users
														LEFT JOIN locations ON user_training_location = location_id
														$w_str
														GROUP BY user_training_location
														ORDER BY location_name
														");
			while($data2 = $this->db2->get_next_res())
			{
				$this->db->sql_query("SELECT * FROM users $w_str AND user_training_location='$data2->user_training_location' ORDER BY user_account ASC");
				$x.= "<h1>$data2->location_name (".$this->db->count().")</h1>";
				while($data = $this->db->get_next_res())
				{
					$my_user = new user($data->user_id);
					$x.= $my_user->get_picture(true,$js_event_name,null,true);
					$my_user = null;
				}
				$x.="<div style='clear:both;border-bottom:1px solid gray;'>&nbsp;</div>";
			}
		}

		if($group_by=='age')
		{
			$this->db2 = clone($this->db);
			$this->db2->sql_query("SELECT YEAR(CURRENT_DATE) - YEAR(user_birthday)
	    - (DATE_FORMAT(CURRENT_DATE, '%m%d') < DATE_FORMAT(user_birthday, '%m%d')) as diff_years
	    FROM users $w_str GROUP BY YEAR(CURRENT_DATE) - YEAR(user_birthday)
	    - (DATE_FORMAT(CURRENT_DATE, '%m%d') < DATE_FORMAT(user_birthday, '%m%d')) ORDER BY diff_years");
			while($data2 = $this->db2->get_next_res())
			{
				if($data2->diff_years=='')
				{
					$this->db->sql_query("SELECT * FROM users $w_str AND user_birthday IS NULL ORDER BY user_account ASC");
				}
				else
				{
					$this->db->sql_query("SELECT * FROM users $w_str AND YEAR(CURRENT_DATE) - YEAR(user_birthday) - (DATE_FORMAT(CURRENT_DATE, '%m%d') < DATE_FORMAT(user_birthday, '%m%d'))='$data2->diff_years' ORDER BY user_birthday DESC");
				}
				if($data2->diff_years=='')
				{
					$x.= "<h1>Unbekannt (".$this->db->count().")</h1>";
				}
				else
				{
					$x.= "<h1>".$data2->diff_years." Jahre (".$this->db->count().")</h1>";
				}
				while($data = $this->db->get_next_res())
				{
					$my_user = new user($data->user_id);
					$x.= $my_user->get_picture(true,$js_event_name,null,true);
					$my_user = null;
				}
				$x.="<div style='clear:both;border-bottom:1px solid gray;'>&nbsp;</div>";
			}
		}

		return $x;
	}


	function calc_BHZ()
	{
		$tournament_id = $this->id;

		$users = clone($this->db);
		$db2 = clone($this->db);

		//Insert count of wins
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;
			$this->db->sql_query("SELECT * FROM games WHERE game_group_id='$tournament_id' AND game_status='Closed' AND (game_winner_id='$my_user_id' OR game_winner2_id='$my_user_id')");
			$wins = $this->db->count();
			$this->db->sql_query("UPDATE group2user SET group2user_wins='$wins' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");
		}

		//Insert BHZ
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;

			$BHZ = 0;
			if($this->get_system()=='Doppel_dynamisch')
			{
				//Get own wins
				$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$my_user_id'");
				$BHZ = $d2->group2user_wins;

				//Get all games with current player involved
				$this->db->sql_query("SELECT * FROM games
												WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id' OR game_player3_id = '$my_user_id' OR game_player4_id='$my_user_id')");

				while($d = $this->db->get_next_res())
				{
					$p1_wins=0; $p2_wins=0; $p3_wins=0; $p4_wins=0;

					//Get wins for all involved players
					$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player1_id'");
					$p1_wins = $d2->group2user_wins;

					$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player2_id'");
					$p2_wins = $d2->group2user_wins;

					if($d->game_player3_id>0)
					{
						$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player3_id'");
						$p3_wins = $d2->group2user_wins;

						$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player4_id'");
						$p4_wins = $d2->group2user_wins;
					}

					//Calculcate BHZ by subtracting wins of partner and add wins of both opponents
					if($d->game_player1_id==$my_user_id) { $BHZ = $BHZ - $p3_wins + $p2_wins + $p4_wins; }
					if($d->game_player2_id==$my_user_id) { $BHZ = $BHZ - $p4_wins + $p1_wins + $p3_wins; }
					if($d->game_player3_id==$my_user_id) { $BHZ = $BHZ - $p1_wins + $p2_wins + $p4_wins; }
					if($d->game_player4_id==$my_user_id) { $BHZ = $BHZ - $p2_wins + $p1_wins + $p3_wins; }
				}
			}
			else
			{
				$this->db->sql_query("SELECT *,CASE '$my_user_id' WHEN game_player1_id THEN game_player2_id WHEN game_player2_id THEN game_player1_id WHEN game_player3_id THEN game_player4_id WHEN game_player4_id THEN game_player3_id END player
												FROM games
												WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id' OR game_player3_id='$my_user_id' OR game_player4_id='$my_user_id')");

				while($d = $this->db->get_next_res())
				{
					$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->player'");
					$BHZ = $BHZ + $d2->group2user_wins;
				}
			}
			$this->db->sql_query("UPDATE group2user SET group2user_BHZ='$BHZ' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");
		}

		//Insert Fine-BHZ
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;
			$BHZ = 0;
			if($this->get_system()=='Doppel_dynamisch')
			{
				//Get own BHZ
				$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$my_user_id'");
				$BHZ = $d2->group2user_BHZ;

				//Get all games with current player involved
				$this->db->sql_query("SELECT * FROM games
												WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id' OR game_player3_id = '$my_user_id' OR game_player4_id='$my_user_id')");

				while($d = $this->db->get_next_res())
				{
					$p1_wins=0; $p2_wins=0; $p3_wins=0; $p4_wins=0;

					//Get BHZ for all involved players
					$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player1_id'");
					$p1_wins = $d2->group2user_BHZ;

					$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player2_id'");
					$p2_wins = $d2->group2user_BHZ;

					if($d->game_player3_id>0)
					{
						$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player3_id'");
						$p3_wins = $d2->group2user_BHZ;

						$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player4_id'");
						$p4_wins = $d2->group2user_BHZ;
					}

					//Calculcate FBHZ by subtracting BHZ of partner and add BHZ of both opponents
					if($d->game_player1_id==$my_user_id) { $BHZ = $BHZ - $p3_wins + $p2_wins + $p4_wins; }
					if($d->game_player2_id==$my_user_id) { $BHZ = $BHZ - $p4_wins + $p1_wins + $p3_wins; }
					if($d->game_player3_id==$my_user_id) { $BHZ = $BHZ - $p1_wins + $p2_wins + $p4_wins; }
					if($d->game_player4_id==$my_user_id) { $BHZ = $BHZ - $p2_wins + $p1_wins + $p3_wins; }
				}
			}
			else
			{
				$this->db->sql_query("SELECT *,CASE '$my_user_id' WHEN game_player1_id THEN game_player2_id WHEN game_player2_id THEN game_player1_id WHEN game_player3_id THEN game_player4_id WHEN game_player4_id THEN game_player3_id END player
												FROM games
												WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id' OR game_player3_id='$my_user_id' OR game_player4_id='$my_user_id')");

				while($d = $this->db->get_next_res())
				{
					$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->player'");
					$BHZ = $BHZ + $d2->group2user_BHZ;
				}
			}
			$this->db->sql_query("UPDATE group2user SET group2user_FBHZ='$BHZ' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");
		}

		//if doubles is played, combine the BHZ of the two players
		if($this->get_system()=='Doppel_fix')
		{
			$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
			$db2 = clone($this->db);
			$arr_done = array();
			while($my_user = $users->get_next_res())
			{
				if($my_user->group2user_partner_id>0)
				{
					if(!in_array($my_user->group2user_user_id,$arr_done))
					{
						$arr_done[] = $my_user->group2user_user_id;
						$arr_done[] = $my_user->group2user_partner_id;
						$BHZ=0; $FBHZ=0;
						$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id' AND group2user_user_id='$my_user->group2user_partner_id'");
						$BHZ = ($d2->group2user_BHZ + $my_user->group2user_BHZ) / 2;
						$FBHZ = ($d2->group2user_FBHZ + $my_user->group2user_FBHZ) / 2;
						$this->db->sql_query("UPDATE group2user SET group2user_FBHZ='$FBHZ', group2user_BHZ='$BHZ' WHERE group2user_user_id='$my_user->group2user_user_id' AND group2user_group_id='$tournament_id'");
						$this->db->sql_query("UPDATE group2user SET group2user_FBHZ='$FBHZ', group2user_BHZ='$BHZ' WHERE group2user_user_id='$d2->group2user_user_id' AND group2user_group_id='$tournament_id'");
					}
				}
			}
		}
	}

	function update_winners()
	{
		$tournament_id = $this->id;

		$users = clone($this->db);

		//Insert count of wins
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;
			$this->db->sql_query("SELECT * FROM games WHERE game_group_id='$tournament_id' AND game_winner_id='$my_user_id'");
			$wins = $this->db->count();
			$this->db->sql_query("UPDATE group2user SET group2user_wins='$wins' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");
		}

		//Insert set/points won
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;
			$sets_won = 0; $sets_loose = 0;
			$points_won = 0; $points_loose = 0;
			$this->db->sql_query("SELECT * FROM games
											WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id')");

			while($d = $this->db->get_next_res())
			{
				if($d->game_player1_id==$my_user_id)
				{
					$points_won = $points_won + $d->game_set1_p1 + $d->game_set2_p1 + $d->game_set3_p1;
					$points_loose = $points_loose + $d->game_set1_p2 + $d->game_set2_p2 + $d->game_set3_p2;
					if($d->game_set1_p1>$d->game_set1_p2) { $sets_won++; } if($d->game_set1_p2>$d->game_set1_p1) { $sets_loose++; }
					if($d->game_set2_p1>$d->game_set2_p2) { $sets_won++; } if($d->game_set2_p2>$d->game_set2_p1) { $sets_loose++; }
					if($d->game_set3_p1>$d->game_set3_p2) { $sets_won++; } if($d->game_set3_p2>$d->game_set3_p1) { $sets_loose++; }
				}
				else
				{
					$points_won = $points_won + $d->game_set1_p2 + $d->game_set2_p2 + $d->game_set3_p2;
					$points_loose = $points_loose + $d->game_set1_p1 + $d->game_set2_p1 + $d->game_set3_p1;
					if($d->game_set1_p2>$d->game_set1_p1) { $sets_won++; } if($d->game_set1_p1>$d->game_set1_p2) { $sets_loose++; }
					if($d->game_set2_p2>$d->game_set2_p1) { $sets_won++; } if($d->game_set2_p1>$d->game_set2_p2) { $sets_loose++; }
					if($d->game_set3_p2>$d->game_set3_p1) { $sets_won++; } if($d->game_set3_p1>$d->game_set3_p2) { $sets_loose++; }
				}
			}

			$my_sets = $sets_won - $sets_loose;
			$my_points = $points_won - $points_loose;

			$this->db->sql_query("UPDATE group2user SET group2user_BHZ='$my_sets',group2user_FBHZ='$my_points' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");

			//Special für Direktbegegnungen bei 2 Spielern mit gleich viel Siegen
			if($this->get_counting()=='win')
			{
				$this->db->sql_query("UPDATE group2user SET group2user_BHZ='0' WHERE group2user_group_id='$tournament_id'");
				$users->sql_query("SELECT COUNT(*) as anz, MAX(group2user_wins) as anz_wins FROM group2user WHERE group2user.group2user_group_id = '$tournament_id' GROUP BY group2user_wins HAVING COUNT(*)='2'");
				$temp = clone($users);
				while($d = $users->get_next_res())
				{
					$temp->sql_query("SELECT * FROM group2user WHERE group2user_group_id = '$tournament_id' AND group2user_wins = '".$d->anz_wins."'");
					$d_p1 = $temp->get_next_res();
					$d_p2 = $temp->get_next_res();

					$p1 = $d_p1->group2user_user_id;
					$p2 = $d_p2->group2user_user_id;

					$temp->sql_query("SELECT * FROM games WHERE (game_player1_id = '$p1' AND game_player2_id = '$p2' AND game_group_id='$tournament_id') OR (game_player2_id = '$p1' AND game_player1_id = '$p2' AND game_group_id='$tournament_id')");
					if($temp->count()==1)
					{
						$d_temp = $temp->get_next_res();
						$this->db->sql_query("UPDATE group2user SET group2user_BHZ='1' WHERE group2user_user_id='".$d_temp->game_winner_id."' AND group2user_group_id='$tournament_id'");
					}

				}
			}


		}

	}


	function get_partner_definition()
	{
		$x = "";
		$arr_users = array();

		for($i=1;$i <= $this->get_number_of_players()/2;$i++)
		{
			$x.= "<div style='float:left;width:280px;height:220px;border:1px solid gray;border-radius:1vw;padding:1vw;margin:0.5vw;'>";
			$x.= "Team $i<p>";
			$w_str = "WHERE group2user_group_id='".$this->id."'";
			foreach($arr_users as $u)
			{
				$w_str.= " AND group2user_user_id!='$u'";
			}
			$data = $this->db->sql_query_with_fetch("SELECT * FROM group2user LEFT JOIN users ON group2user_user_id=users.user_id $w_str ORDER BY group2user_partner_id DESC, user_account LIMIT 1");
			$myUser = new user($data->user_id);
			$arr_users[] = $data->user_id;
			$x.= $myUser->get_picture();
			if($data->group2user_partner_id!='')
			{
				$myUser = new user($data->group2user_partner_id);
				$arr_users[] = $data->group2user_partner_id;
				$x.= "<div style='width:10px;float:left;'>&nbsp;</div>".$myUser->get_picture();
				$x.= "<button style='background-color:red;' onclick='delete_team($myUser->id);'>Team löschen</button>";

			}
			else
			{
				$x.= "<img src='".level."inc/imgs/undefined.png' style='width:120px;padding-left:10px;'/>";
				break;
			}
			$x.= "</div>";
		}
		$x.= "";
		return $x;
	}

	function get_seeding_definition()
	{
		$x = "";
		$arr_users = array();

		$anz_seeds = round($this->get_number_of_players()/2,0);
		if($anz_seeds>8) { $anz_seeds = 8; }

		for($i=1;$i <= $anz_seeds;$i++)
		{
			$x.= "<div style='text-align:center;float:left;width:140px;height:180px;border:1px solid gray;border-radius:1vw;padding:1vw;margin:0.5vw;'>";
			$x.= "Setzplatz $i<p>";
			$this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id= :t_id AND group2user_seeded= :curr_seed",array('t_id'=>$this->id, 'curr_seed'=>$i));

			if($this->db->count()>0)
			{
				$data = $this->db->get_next_res();
				$myUser = new user($data->group2user_user_id);
				$x.= "<div style='margin:auto;width:120px;'>".$myUser->get_picture()."</div>";
			}
			else
			{
				$x.="<img style='width:120px;' src='".level."/inc/imgs/question.png' />";
			}
			$x.= "</div>";
		}
		$x.= "";
		return $x;
	}

}
?>
