<?php
//*****************************************************************************
//20.12.2011 Claude H�bscher
//-----------------------------------------------------------------------------
//this class can be used to read and modify header information
//for example:
//$h = new header_mod;
//print "<a href='".$h->change_parameter('action','new')."'>Add new entry</a>";
//*****************************************************************************
class header_mod
{
  public $path;               //path without parameters
  public $filename;           //filename
  public $directory;          //directory name of the current file

  private $curr_parameters;   //Internal variable with the parameters

  //initialize
  //save the current header information in the public variables
  function __construct()
  {
    $this->reset();
  }

  function reset()
  {
    $this->curr_parameters = "";
    //filename
    $this->curr_parameters = $this->get_parameters_as_string();
    if(isset($_SERVER['REQUEST_URI']))
    {
        $this->filename = basename($_SERVER['REQUEST_URI']);
        if(strpos($this->filename,"?")!==FALSE) { $this->filename = substr($this->filename,0,strpos($this->filename,"?")); }
        //if only folder, set it to index.php
        if(strpos($this->filename,".")==0) { $this->filename = "index.php"; }
    }

    //path
    if(isset($_SERVER['DOCUMENT_ROOT']) AND isset($_SERVER['SCRIPT_FILENAME']))
    {
      $this->path = str_replace($_SERVER['DOCUMENT_ROOT'],"",$_SERVER['SCRIPT_FILENAME']);
      $this->path = substr($this->path,1);
      $this->path = substr($this->path,strpos($this->path,"/"));
      $this->path = substr($this->path,1);
    }

    //directory
    $this->directory = substr($this->path,0,strrpos($this->path,"/"));
    if(strpos($this->directory,"/")!=0) { $this->directory = substr($this->directory,strrpos($this->directory,"/")+1); }
  }

  //return filename with parameters
  function get_link()
  {
    if(trim($this->curr_parameters)!='') { return $this->filename."?".$this->curr_parameters; }
    else { return $this->filename; }
  }

  //return the parameters in one string
  function get_parameters_as_string()
  {
    if($this->curr_parameters=='')
    {
      $parameters = "";
      if(isset($_SERVER['REQUEST_URI']))
      {
        $filename = basename($_SERVER['REQUEST_URI']);
        if(strpos($filename,"?")!==FALSE) { $parameters = substr($filename,strpos($filename,"?")+1); }
      }
      return $parameters;
    }
    else
    {
      return $this->curr_parameters;
    }
  }

  //return all parameters in a 2-dimensional array (parameter, value)
  function get_parameters_as_array()
  {
    $parameters = preg_split("[&|&]",$this->curr_parameters);
    foreach($parameters as $para)
    {
      $arr_para[] = array('parameter'=>substr($para,0,strpos($para,"=")),'value'=>substr($para,strpos($para,"=")+1));
    }
    return $arr_para;
  }

  //return a parameter
  function get_parameter($parameter_name)
  {
    $parameters = preg_split("[&|&]",$this->curr_parameters);
    foreach($parameters as $para)
    {
      if(substr($para,0,strpos($para,"="))==$parameter_name) { return substr($para,strpos($para,"=")+1); break; }
    }
  }

  //change a parameter with a new value, if it does not exist it will be appended
  function change_parameter($parameter,$value)
  {
    $str = "";
    $found = false;
    $paras = $this->get_parameters_as_array();
    foreach($paras as $para)
    {
      if(trim($para['parameter'])!='')
      {
        if($para['parameter']==$parameter)
        {
          $para['value'] = $value;
          $found = true;
        }
        $str .= $para['parameter']."=".$para['value']."&";
      }
    }
    //New parameter
    if(!$found)
    {
      $str .= $parameter."=".$value."&";
    }
    $this->curr_parameters = substr($str,0,strlen($str)-1);
    if(trim($this->curr_parameters)=='') { return $this->filename();  }
    else { return $this->filename."?".$this->curr_parameters; }
  }

  //remove a parameter
  function remove_parameter($parameter)
  {
    $str = "";
    $paras = $this->get_parameters_as_array();
    foreach($paras as $para)
    {
      if($para['parameter']!=$parameter)
      {
        $str .= $para['parameter']."=".$para['value']."&";
      }
    }
    $this->curr_parameters = substr($str,0,strlen($str)-1);
    if(trim($this->curr_parameters)=='') { return $this->filename;  }
    else { return $this->filename."?".$this->curr_parameters; }
  }
}
?>
