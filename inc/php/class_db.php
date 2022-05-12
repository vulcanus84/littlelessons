<?php
//*****************************************************************************
//26.03.2013 Claude Hübscher
//-----------------------------------------------------------------------------
//this class can be used to access to a database
//The class throws exception, therefor its necessary to try/catch
//*****************************************************************************
class db
{
	/**
	 *contain the id of the last INSERT statement in the database
	 */
  public $last_inserted_id=NULL;
	/**
	 *contain the connection string to the database
	 */
	public $connected_host=NULL;
	/**
	 *contain the name of the connected database
	 */
	public $connected_db=NULL;

  public $connection=NULL;
  private $res=NULL;
  private $counter=NULL;
  private $logger;

  function __construct($host,$user,$pw,$database)
  {
    //connect to database
    $this->connection = new PDO("mysql:host=$host;dbname=$database", $user, $pw,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    $this->connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    if ($this->connection===False)
    { 
      throw new Exception ("<table style='text-align:left;'>
                              <tr>
                                <td style='width:200px;'><b>SQL-Error (Connect):</b></td>
                                <td>".$this->connection->e->getMessage()."</td>
                              </tr>
                            </table>");
    }
		else
		{
			$this->connected_host = $host;
			$this->connected_database = $database;
		}
		$this->logger = new log();
  }
  
  function insert($arr_fields,$table)
  {
    try
    {
      $str_keys = "";
      $str_vals = "";
      foreach($arr_fields as $k => $v)
      {
        $str_keys.=':'.$k.',';
        $str_vals.=$v.',';
      } 
      $str_keys = substr($str_keys,0,-1);
      $str_vals = substr($str_vals,0,-1);

      $STH = $this->connection->prepare("INSERT INTO ".$table." (".str_replace(':','',$str_keys).") VALUES (".$str_keys.")");
      $STH->execute($arr_fields);
      $this->last_inserted_id = $this->connection->lastInsertId();
      if($table!='log') { $this->logger->write_to_log('Database', "INSERT INTO ".$table." (".str_replace(':','',$str_keys).") VALUES (".$str_vals.")"); }
    }
    catch (PDOException $e)
    {
      throw new Exception("<div style='border:1px dotted black;color:red;text-align:center;padding:5px;margin:5px;font-weight:bold;'>
                ".$e->getMessage()."
            </div>");
    }
  }
  
  function update($arr_fields,$table,$id_column,$id)
  {
    try
    {
      $str_keys = "";
      $str_vals = "";
      $i=0;
      foreach($arr_fields as $k => $v)
      {
      	if($v=='CURRENT_TIMESTAMP') { $str_keys.=$k."=CURRENT_TIMESTAMP,"; $str_vals.=$k."=CURRENT_TIMESTAMP,"; unset($arr_fields[$k]); }
      	elseif($v=='NULL') { $str_keys.=$k."=NULL,"; $str_vals.=$k."=NULL,"; unset($arr_fields[$k]); }
      	else { $str_keys.=$k."=:".$k.","; $str_vals.=$k."='".$v."',"; }
      	$i++;
      }
      $str_keys = substr($str_keys,0,-1);       
      $str_vals = substr($str_vals,0,-1);
      
      $STH = $this->connection->prepare("UPDATE ".$table." SET ".$str_keys." WHERE ".$id_column."=:my_id");
			$arr_fields['my_id'] = $id;
      $STH->execute($arr_fields);
      if($table!='log') { $this->logger->write_to_log('Database', "UPDATE ".$table." SET ".$str_vals." WHERE ".$id_column."='".$id."'"); }
    }
    catch (PDOException $e)
    {
      throw new Exception("<div style='border:1px dotted black;color:red;text-align:center;padding:5px;margin:5px;font-weight:bold;'>
                ".$e->getMessage()."
            </div>");
    }
  }


  function delete($table,$id_column,$id)
  {
    try
    {
      $STH = $this->connection->prepare("DELETE FROM ".$table." WHERE ".$id_column."=:my_id");
			$arr_fields['my_id'] = $id;
      $STH->execute($arr_fields);
      if($table!='log') { $this->logger->write_to_log('Database', "DELETE FROM ".$table." WHERE ".$id_column."='".$id."'"); }
    }
    catch (PDOException $e)
    {
      throw new Exception("<div style='border:1px dotted black;color:red;text-align:center;padding:5px;margin:5px;font-weight:bold;'>
                ".$e->getMessage()."
            </div>");
    }
  }

  
  function sql_query($sql,$parameters=null)
  {
    try
    {
      $this->res = $this->connection->prepare($sql,array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL ));
      if(is_array($parameters)) { $this->res->execute($parameters); } else { $this->res->execute(); }
    }
    catch (PDOException $e)
    {
      throw new Exception("<table style='text-align:left;'>
                              <tr>
                                <td style='width:200px;'><b>SQL-Error (SQL-Query):</b></td>
                                <td>".$e->getMessage()."</td>
                              </tr>
                              <tr>
                                <td><b>SQL-Command:</b></td>
                                <td>".$sql."</td>
                              </tr>
                            </table>");
    }
    
    $this->counter = null;
    return $this->res;
  }

  //For SQL Statements with one result
  function sql_query_with_fetch($sql,$parameters=null)
  {
    try
    {
      $this->res = $this->sql_query($sql,$parameters);
      if($this->res)
      {
        return $this->res->fetchObject();
      }
    }
    catch (PDOException $e)
    {
      throw new Exception("<table style='text-align:left;'>
                              <tr>
                                <td style='width:200px;'><b>SQL-Error (SQL-Query):</b></td>
                                <td>".$e->getMessage()."</td>
                              </tr>
                              <tr>
                                <td><b>SQL-Command:</b></td>
                                <td>".$sql."</td>
                              </tr>
                            </table>");
    }
  }
      
  function get_next_res()
  {
    if($this->res)
    {
      return $this->res->fetchObject();
    }
  }
  
  public function count() 
  {
  	$this->counter=$this->res->rowCount();
 	  return $this->counter;
  }

	/**
	 *Set it to -1 to iterate from beginning with get_next_res
	 */
	public function seek($item)
	{
    if($this->res)
    {
      $this->res->fetch(PDO::FETCH_OBJ, PDO::FETCH_ORI_NEXT, $item);
    }
	}
}

?>