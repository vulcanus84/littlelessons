<?php
class html
{
  private $txt;
	private $t;

  function __construct($db)
  {
    if(isset($_SESSION['login_user']))
		{
			$this->t = new translation(clone($db),$_SESSION['login_user']->get_frontend_language());
		}
		else
		{
			$this->t = new translation($db);
		}
  }

  //returns the html-string for a selection ($name_col -> column with the data for POST / $display_col -> column with the data to display)
  function get_selection($db, $select_name, $name_col, $display_col,$javascript)
  {
    $txt = "  <select name='$select_name' id='$select_name' $javascript>\n";
    $txt.= "    <option value=''>".$this->t->translate("-- Bitte auswählen --")."</option>\n";
    while($data = $db->get_next_res())
    {
      $txt.= "    <option value='".$data->$name_col."'";
      if(isset($_GET[$select_name]) && $_GET[$select_name]==$data->$name_col) { $txt.=" selected='selected'"; }
      $txt.= ">".$data->$display_col."</option>\n";
    }
    $txt.= "  </select>\n";
    return $txt;
  }

	/**
	 *1-dimensional Array, the select_name is the GET variable which the dropdown will be preselected
	 *<br>
	 *EXAMPLE 1:
	 *'Val1,Val2,Val3','modus',"onchange=\"$('#img_content').load('$_SERVER[PHP_SELF]?modus=' + $(this).val());\""
	 *<br>
	 *EXAMPLE 2:
	 *'Val1,Val2,Val3', 'typ',"onchange='document.forms.grps.submit();"
	*/
  public function get_selection_with_array($array, $select_name, $javascript,$with_translation=true)
  {
    $txt = "  <select name='$select_name' id='$select_name' $javascript>\n";
		if(!is_array($array)) { $array = explode(",",$array); }
		foreach($array as $data)
		{
      $txt.= "    <option value='".$data."'";
      if(isset($_POST[$select_name]) && $_POST[$select_name]==$data) { $txt.=" selected='selected'"; }
      if($with_translation) { $txt.= ">".$this->t->translate($data)."</option>\n"; } else { $txt.= ">".$data."</option>\n"; }
		}
    $txt.= "  </select>\n";
    return $txt;
  }

  function get_radio_buttons($form_name,$items,$default='',$javascript='')
  {
		$txt = "";
		foreach($items as $item)
		{
  	  $txt.= $item['display']."  <input type='radio'";
			if(isset($_GET[$form_name]) && $_GET[$form_name]==$item['value'])
			{
				$txt.= " checked='checked'";
			}
			else
			{
				if($item['value']==$default) { $txt.= " checked='checked'"; }
			}
			$txt.= " name='$form_name' value='$item[value]' $javascript>\n";
		}
    return $txt;
  }

}
?>