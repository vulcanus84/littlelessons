<?php
/*
  --------------------------------------------
  Created by Claude Hübscher, HZI, 18.02.2015
  --------------------------------------------
*/

class column
{
  public $db_col_name;
  public $col_name;
  public $col_type;
	public $show_on_list=true;
	public $show_on_edit=true;

  private $selection; //2-dimensional array (value | display)
  private $colDbName_for_list;
  private $width; 
	private $height; //only relevant for areas
  private $edit_typ; //checkbox, date, datetime, not_editable
  private $save_column;
  private $filter_column;
	private $sort_column;
	private $link;
	private $default_value;
	private $javascript;
	private $remark;
	private $disabled;
	private $hidden;

  public function __construct($db_col_name,$col_name)
  {
    $this->db_col_name = $db_col_name;
    $this->col_name = $col_name;
  }

  public function set_width($width)
  {
    $this->width = $width;
  }
  public function get_width()
  {
    if(isset($this->width))
    {
      return $this->width;
    }
    else
    {
			if($this->get_edit_typ()=='radio' OR $this->get_edit_typ()=='checkbox') { return 15; } else { return 100; }
    }
  }

  public function set_remark($remark)
  {
    $this->remark = $remark;
  }
  public function get_remark()
  {
    if(isset($this->remark)) { return $this->remark; } else { return null;}
  }

	public function set_disabled($mode=true)
	{
		$this->disabled=$mode;
	}
  public function get_disabled()
  {
    if(isset($this->disabled)) { return $this->disabled; } else { return false; }
  }

	public function set_hidden($mode=true)
	{
		$this->hidden=$mode;
	}
  public function get_hidden()
  {
    if(isset($this->hidden)) { return $this->hidden; } else { return false; }
  }

	//only relevant for areas
  public function set_height($height)
  {
    $this->height = $height;
  }
  public function get_height()
  {
    if(isset($this->height)) { return $this->height; } else { return 100; }
  }

	/**
	 *Possible values:
	 *checkbox
	 *radio
	 *area
	 *date
	 *datetime
	 *not_editable
	 *hidden
	 *upload
	 */
  public function set_edit_typ($typ)
  {
    switch($typ)
    {
      case 'checkbox':
        $this->edit_typ = 'checkbox'; break;
      case 'radio':
        $this->edit_typ = 'radio'; break;
      case 'area':
        $this->edit_typ = 'area'; break;
      case 'date':
        $this->edit_typ = 'date'; break;
      case 'datetime':
        $this->edit_typ = 'datetime'; break;
      case 'not_editable':
        $this->edit_typ = 'not_editable'; break;
      case 'hidden':
        $this->edit_typ = 'hidden'; break;
      case 'upload':
        $this->edit_typ = 'upload'; break;
      default:
        throw new exception("Unexpected Edit typ \"".$typ."\"");
    }
  }
  public function get_edit_typ()
  {
    return $this->edit_typ;
  }

  public function set_save_column($save_col)
  {
    $this->save_column = $save_col;
  }

  //if a special column name for saving is defined, return this, otherwise the column db name
  public function get_save_column()
  {
    if(isset($this->save_column))
    {
      return $this->save_column;
    }
    else
    {
      return $this->db_col_name;
    }
  }

  public function set_filter_column($save_col)
  {
    $this->filter_column = $save_col;
  }

  //if a special column name for saving is defined, return this, otherwise the column db name
  public function get_filter_column()
  {
    if(isset($this->filter_column))
    {
      return $this->filter_column;
    }
    else
    {
      if($this->get_colDbName_for_list())
      {
        return $this->get_colDbName_for_list();
      }
      else
      {
        return $this->db_col_name;
      }
    }
  }

  public function set_sort_column($sort_col)
  {
    $this->sort_column = $sort_col;
  }

  //check_filter column for spaces und replace it by comma
  public function get_sort_column()
  {
    if(isset($this->sort_column))
    {
      $x = $this->sort_column;
    }
    else
    {
      $x = $this->get_filter_column();
    }
		if(substr_count($x,' ')>0)
		{
			$x = str_replace('+','',$x);
			$x = str_replace('\'','',$x);
			$x = preg_replace('/\040{1,}/',' ',$x);
			$x = str_replace(' ',',',$x);
		}
		return $x;
  }

  //Selection can set directly by array or csv
  //This function change it to array
  public function set_selection($selection)
  {
    unset($this->selection);
    if (is_array($selection)===FALSE)
    {
      $arr_tmp = explode(",",$selection);
      foreach($arr_tmp as $c_value)
      {
        $this->selection[] = array("value"=>$c_value,"display"=>$c_value);
      }
    }
    else
    {
      $this->selection=$selection;
    }
  }

  public function set_selection_by_sql($db,$colName_for_list,$id_col,$description='')
  {
    if($description!='')
		{
			$t = new translation(clone($db),$_SESSION['login_user']->get_frontend_language());
			$description = $t->translate($description);
			$arra[ ] = array('display' => $description,'value' => "");
		}
		else
		{
			$arra[ ] = array('display' => "--",'value' => "NULL");
		}
    while ($daten = $db->get_next_res())
    {
      $arra[ ] = array('display' => $daten->$colName_for_list,'value' => $daten->$id_col);
    }
    $this->set_selection($arra);
    $this->colDbName_for_list = $colName_for_list;
  }

  public function get_colDbName_for_list()
  {
    if($this->colDbName_for_list!='') {return $this->colDbName_for_list; } else { return false; }
  }

  public function set_colDbName_for_list($colName)
  {
    $this->colDbName_for_list=$colName;
  }

  public function get_selection()
  {
    if(isset($this->selection)) { return $this->selection; } else { return false; }
  }

	public function hide_from_list()
	{
		$this->show_on_list=false;
	}

	public function hide_from_edit()
	{
		$this->show_on_edit=false;
	}

	public function set_link($link)
	{
		$this->link = $link;
	}

	public function get_link()
	{
		return $this->link;
	}

	public function set_default_value($value)
	{
		$this->default_value= $value;
	}

	public function get_default_value()
	{
    if(isset($this->default_value))
		{
			if(gettype($this->default_value)=='object')
			{
				return $this->default_value->format('d.m.Y');
			}
      return $this->default_value;
    }
    else
    {
			return null;
		}
	}

	public function set_javascript($javascript)
	{
		$this->javascript = $javascript;
	}

	public function get_javascript()
	{
    if(isset($this->javascript))
    {
      return $this->javascript;
    }
    else
    {
			return null;
		}
	}

}

?>