<?php
//*****************************************************************************
//19.09.2012 Claude Hübscher
//-----------------------------------------------------------------------------
//this class manage the permission in the ccs system
//*****************************************************************************
class right
{
  private $connection=NULL;
  private $res=NULL;
  private $counter=NULL;
    
  function __construct($db,$user)
  {
    $this->db = $db;
    $this->user = $user; 
  }

  function get_permission($filename)
  {
    $this->db->sql_query("SELECT * FROM rights WHERE user_id='$this->user->user_id'");
    while($this->get_next_res())
    {
      if(strpos($id,$d['right_id'])!==FALSE)
      {
        $this->db->sql_query("SELECT * FROM rights WHERE right_id='$d[right_id]'");
        break;
      }
    }
    if($this->db->count()>0)
    {
      $d = $this->db->get_next_res();
      return $d['right'];
    }
    else
    {
      return false;
    }
    return "R";     //Read
    return "RW";    //Read Write
    return "RWA";   //Read Write Add
    return "RWAD";  //Read Write Add Delete
  }


  function set_permission($path,$permission)
  {

  }

}

?>