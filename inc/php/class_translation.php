<?php
//*****************************************************************************
//11.09.2014 Claude Hübscher
//-----------------------------------------------------------------------------
//this class is used for translation by an sql database
//*****************************************************************************
class translation
{
  private $db;            //Pointer to DB-Class
  private $language_code='german';  //Code for target language
  private $search_str;     //String with all availabel column name to search for the phrase

  private $translation_table;

  //the language code represents the column name in the sql table
  function __construct($db, $lang_code='german')
  {
    $this->translation_table = "translation";
    $this->search_str="";
    $this->language_code = $lang_code;
    $this->db = clone($db);
    $this->db->sql_query("SHOW COLUMNS FROM ".$this->translation_table);
    while($d = $this->db->get_next_res())
    {
      if($d->Field!='trans_id' && $d->Field!='trans_last_used_on' && $d->Field!='trans_last_used_by' && $d->Field!='trans_created_on') { $this->search_str.= " OR ".$d->Field."='XX'"; }
    }
    $this->search_str = substr($this->search_str,4);
  }

  function translate($text,$only_read=false)
  {
		$t_text = preg_replace( "/\r|\n/", "", $text);
		$t_text = strip_tags($t_text);
		$t_text = trim($t_text);
		if(trim($t_text)!='')
		{
	    $my_search_str = str_replace("XX",$t_text,$this->search_str);
      $this->db->sql_query("SELECT * FROM $this->translation_table WHERE $my_search_str");
	    if($this->db->count()>0)
	    {
	      $d = $this->db->get_next_res();
				$this->db->sql_query("UPDATE $this->translation_table SET trans_last_used_by='".$_SERVER['PHP_SELF']."', trans_last_used_on=NOW() WHERE $my_search_str");
	      //If the search phrase is found, but there is no translation, return the search phrase
        $language_code = 'trans_'.$this->language_code;
        //print_r($d);
	      if(trim($d->$language_code)!='') { return str_replace($t_text,$d->$language_code,$text); } else { return $text; }
	    }
	    else
	    //If the the search is not allready in the table, add it
	    {
	      if(!$only_read) { $this->db->sql_query("INSERT INTO $this->translation_table (trans_code,trans_german,trans_last_used_by,trans_last_used_on) VALUES ('$t_text','$t_text','".$_SERVER['PHP_SELF']."',NOW())"); }
	      return $text;
	    }
		}
		else
		{
			return $text;
		}
  }

	public function set_language($lang_code)
	{
		$this->language_code = $lang_code;
	}

	public function get_language()
	{
		if(isset($this->language_code)) { return $this->language_code; } else { return null; }
	}

}
?>