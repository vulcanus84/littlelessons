<?php
/*
  Create a query from sql-data and let them filter, edit, etc.
  Class column is below and works together
  Dependencies: jQuery, PHP
  --------------------------------------------
  Created by Claude Hübscher, HZI, 08.08.2013
  --------------------------------------------
*/
require_once("class_column.php");
require_once("class_PHPExcel.php");

class query
{
  public $db;          //Pointer to DB-Class
  public $row1_css;     //OPTIONAL: CSS Class for alternating style
  public $row2_css;     //OPTIONAL: CSS Class for alternating style
  public $width;    //Width of the query area
  public $height=500;   //Height of the query area
  public $max_rows=50; //Max Number of row to display before next page (Performance)
	public $debug=false;

	/**
	 *Save the current WHERE string ($_SESSION[curr_where_string]) und the filters ($_SESSION[curr_filters]) in the Session
	 */
	public $save_filters_in_session=false;

  private $sql_table;    //Name of the SQL table
  private $id_column;    //Column with unique id for editing/delete
  private $columns;     //Array of columns (class column)
  private $sql_select;  //Special SQL Command for select (e.g. JOIN's or calculated columns)
  private $default_where;
  private $reload;      //If true, the whole page will be reloaded after successfull save/delete
	private $n2n_table;   //In Edit-Mode "Append" necessary
	private $n2n_id_col;  //Column for ID
	private $n2n_target_id_col;  //Column for ID
	private $n2n_target_id_val;  //Column for ID
	private $n2n_fix_col;
	private $n2n_fix_val;

  private $edit_margin;         //??
  private $edit_width;          //??
  private $tbl_width;           //??
  private $edit_mode='no_edit'; //edit (not add or remove), append (only append to something), full (all functions)



  //Initialize class
  public function __construct($db)
  {
    $this->db = $db;
    //******************************
    //CUSTOMIZING
    //******************************
    //Set CSS-Styles für the rows
    $this->row1_css ="liste_1";
    $this->row2_css ="liste_2";
    //******************************
  }

	public function get_export_data($typ)
	{
    $txt = '';
    $this->db->sql_query($this->get_sql_for_list());

		if($typ=='clipboard')
		{
	    foreach($this->columns as $col)
	    {
	      $txt.= $col->col_name."\t";
	    }
	    $txt.= "\n\n";
		}

    while($d = $this->db->get_next_res())
    {
			$export=false;
			if($this->edit_mode=='append')
			{
				$db2 = clone $this->db;
        $id_column = $this->id_column;
				$check_str = "SELECT * FROM ".$this->n2n_table." WHERE ".$this->n2n_id_col."='".$d->$id_column."' AND ".$this->n2n_target_id_col."='".$this->n2n_target_id_val."'";
				if(isset($this->n2n_fix_col)) { $check_str.= " AND ".$this->n2n_fix_col."='".$this->n2n_fix_val."'"; }
				$db2->sql_query($check_str);
				if($db2->count()>0) { $is_checked=true; } else { $is_checked=false; }
				if((isset($_GET['only_checked']) && $_GET['only_checked']=='1' && $is_checked===true) OR
						(isset($_GET['only_checked']) && $_GET['only_checked']=='0' && $is_checked===false) OR (!isset($_GET['only_checked'])))
				{
					$export = true;
				}
			}
			else
			{
				$export = true;
			}

			if($export === true)
			{
        foreach($this->columns as $col)
        {
					switch($typ)
					{
						case 'clipboard':
	            if($col->get_colDbName_for_list()) { $db = $col->get_colDbName_for_list(); } else { $db = $col->db_col_name; }
	            $txt.= preg_replace( "/\r|\n/", "", $d->$db)."\t";
							break;
						case 'mails':
		          if($d->user_email!='')
		          {
		            if(strpos($txt,$d->user_email)===FALSE) { $txt.= $d->user_email.";"; }
		          }
						}

        }
        $txt.= "\n";
			}
    }
    return $txt;
	}


  //Handle the ajax requests
  public function check_actions()
  {
    if(isset($_GET['ajax']))
    {
      if($_GET['ajax']=='edit') { return $this->get_edit(); }
      if($_GET['ajax']=='multi_edit') { return $this->get_multi_edit(); }
      if($_GET['ajax']=='multi_delete_permission') { return $this->get_multi_delete_permission(); }
      if($_GET['ajax']=='copy') { return $this->get_edit('copy'); }
      if($_GET['ajax']=='save') { try { $this->save(); } catch (Exception $e) { return $e->getMessage(); } }
      if($_GET['ajax']=='multi_save') { try { $this->multi_save(); } catch (Exception $e) { return $e->getMessage(); } }
      if($_GET['ajax']=='saved')
      {
        return "<div style='vertical-align:center;border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg.png\");overflow:hidden;margin:auto;padding:10px;font-size:16pt;'>
                  Datensatz erfolgreich gespeichert
                </div>";
      }
      if($_GET['ajax']=='saving')
      {
        return "<div style='border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg.png\");overflow:hidden;margin:auto;padding:10px;font-size:16pt;'>
                  <img src='".level."inc/imgs/query/loading.gif'/>
                </div>";
      }
      if($_GET['ajax']=='delete_permission') { return $this->get_delete_permission(); }
      if($_GET['ajax']=='deleting')
      {
        return "<div style='border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg_red.png\");overflow:hidden;margin:auto;padding:10px;font-size:16pt;'>
                  <img src='".level."inc/imgs/query/loading.gif'/>
                </div>";
      }
      if($_GET['ajax']=='delete') { try { $this->delete(); } catch (Exception $e) { return $e->getMessage(); }  }
      if($_GET['ajax']=='multi_delete') { try { $this->multi_delete(); } catch (Exception $e) { return $e->getMessage(); }  }
      if($_GET['ajax']=='deleted')
      {
        return "<div style='vertical-align:center;border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg_red.png\");overflow:hidden;margin:auto;padding:10px;font-size:16pt;'>
                  Datensatz erfolgreich gelöscht
                </div>";
      }
      if($_GET['ajax']=='loading')
      {
        return "<div style='border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg.png\");overflow:hidden;margin:auto;padding:10px;font-size:16pt;'>
                  <img src='".level."inc/imgs/query/loading.gif'/>
                </div>";
      }
      if($_GET['ajax']=='error_saving')
      {
        return "<div style='vertical-align:center;border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg_red.png\");overflow:hidden;margin:auto;padding:10px;'>
                  <div style='font-size:16pt;font-weight:bold;padding-bottom:10px;width:100%;border-bottom:1px solid black;'>Datensatz konnte NICHT gespeichert werden!</div><br>"
                  .$_POST['error']."
                  <div id='close' style='cursor:pointer;font-size:12pt;font-weight:bold;margin-top:10px;padding-top:10px;width:100%;border-top:1px solid black;'>Schliessen</div>
                </div>";
      }
      if($_GET['ajax']=='error_deleting')
      {
        return "<div style='vertical-align:center;border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg_red.png\");overflow:hidden;margin:auto;padding:10px;'>
                  <div style='font-size:16pt;font-weight:bold;padding-bottom:10px;width:100%;border-bottom:1px solid black;'>Datensatz konnte NICHT gelöscht werden!</div><br>"
                  .$_POST['error']."
                  <div id='close' style='cursor:pointer;font-size:12pt;font-weight:bold;margin-top:10px;padding-top:10px;width:100%;border-top:1px solid black;'>Schliessen</div>
                </div>";
      }

      if($_GET['ajax']=='clipboard')
      {
				return $this->get_export_data('clipboard');
      }

      if($_GET['ajax']=='export_excel')
      {
        $txt = '';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        // Set document properties
        $objPHPExcel->getProperties()->setCreator($_SESSION['login_user']->login)
        							 ->setTitle("Export");
        $this->db->sql_query($this->get_sql_for_list());
        $objPHPExcel->setActiveSheetIndex(0);
        $cur_col = 0;
        //Write Header Columns and set to bold
        foreach($this->columns as $col)
        {
          $objPHPExcel->setActiveSheetIndex(0)
                      ->setCellValueByColumnAndRow($cur_col,1,$col->col_name);
          $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($cur_col,1)
                      ->getStyle()->getFont()->setBold(true);
          $cur_col++;
        }
  
        //Write data
        $cur_row = 2;
        while($d = $this->db->get_next_res())
        {
          $cur_col = 0;
          foreach($this->columns as $col)
          {
            if($col->get_colDbName_for_list()) { $db = $col->get_colDbName_for_list(); } else { $db = $col->db_col_name; }
            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValueByColumnAndRow($cur_col,$cur_row,$d->$db);
            $cur_col++;
          }
          $cur_row++;
        }
        $cur_col = 0;

        //Activate Autofilter on all columns
        //$objPHPExcel->getActiveSheet()->setAutoFilter($objPHPExcel->getActiveSheet()->calculateWorksheetDimension());

        //Fit Columns width to content (not exactly the same as Excel itself)
        foreach($this->columns as $col)
        {
          $objPHPExcel->getActiveSheet()->getColumnDimension(chr(65 + $cur_col))->setAutoSize(true);
          $cur_col++;
        }
        
        //Set Sheet title
        $objPHPExcel->getActiveSheet()->setTitle("Export");
        
        //Save to temp folder with username and time
        $export_file_name = level."/temp/Export_".time()."_".$_SESSION['login_user']->login.".xlsx";
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($export_file_name);
        
        //return savepath to make download available
        return $export_file_name;
      }

      if($_GET['ajax']=='get_mails')
      {
				return $this->get_export_data('mails');
      }

			if($_GET['ajax']=='append')
      {
        try
        {
  				$check_str = "SELECT * FROM ".$this->n2n_table." WHERE ".$this->n2n_id_col."='".$_GET['id']."' AND ".$this->n2n_target_id_col."='".$this->n2n_target_id_val."'";
  				//if(isset($this->n2n_fix_col)) { $check_str.= " AND ".$this->n2n_fix_col."='".$this->n2n_fix_val."'"; }
  				$this->db->sql_query($check_str);
  				if($this->db->count()>0)
  				{
  					$del_str = str_replace("SELECT *","DELETE",$check_str);
  				  $this->db->sql_query($del_str);
  					$txt = "Removed";
  				}
  				else
  				{
  					$str = str_replace("XXX_ID",$_GET['id'],$this->append_sql_string);
  	        $this->db->sql_query($str);
  					$txt = "Appended";
  				}
        }
        catch (Exception $e) { $txt = $e->getMessage(); }
				return $txt;
			}

      if($_GET['ajax']=='done_append')
      {
				if($_GET['data']=='Appended')
				{
	        return "<div style='vertical-align:center;border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg.png\");overflow:hidden;margin:auto;padding:10px;'>
	                  <div style='font-size:12pt;font-weight:bold;padding-bottom:5px;width:100%;'><img src='".level."inc/imgs/query/finished.png' alt='Finished' style='height:20px;'/>&nbsp;Erfolgreich hinzugefügt</div>
	                </div>";
				}
				if($_GET['data']=='Removed')
				{
	        return "<div style='vertical-align:center;border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg_red.png\");overflow:hidden;margin:auto;padding:10px;'>
	                  <div style='font-size:12pt;font-weight:bold;padding-bottom:5px;width:100%;'><img src='".level."inc/imgs/query/finished.png' alt='Finished' style='height:20px;'/>&nbsp;Erfolgreich entfernt</div>
	                </div>";
				}
      }


      if($_GET['ajax']=='done')
      {
        return "<div style='vertical-align:center;border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg.png\");overflow:hidden;margin:auto;padding:10px;'>
                  <div style='font-size:12pt;font-weight:bold;padding-bottom:10px;width:100%;'>Daten erfolgreich in die Zwischenablage exportiert. <br>Sie können die Daten nun z.B. in Excel einfügen<br><img src='".level."inc/imgs/query/finished.png' alt='Finished'/></div>
                  <div id='close' style='cursor:pointer;font-size:12pt;font-weight:bold;margin-top:10px;padding-top:10px;width:100%;border-top:1px solid black;'>Schliessen</div>
                </div>";
      }

      if($_GET['ajax']=='done_export')
      {
  			$t = new translation(clone $this->db,$_SESSION['login_user']->get_frontend_language());
        return "<div style='vertical-align:center;border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg.png\");overflow:hidden;margin:auto;padding:10px;'>
                  <div style='font-size:12pt;font-weight:bold;padding-bottom:10px;width:100%;'><a  href='$_GET[filename]'><img src='".level."inc/imgs/query/excel_download.png' alt='Excel Download' title='Excel Download'/></a></div>
                  <div id='close' style='cursor:pointer;font-size:12pt;font-weight:bold;margin-top:10px;padding-top:10px;width:100%;border-top:1px solid black;'>Schliessen</div>
                </div>";
      }
    }
    else
    {
      print $this->get_list();
    }
  }

  //Add columns to the array (class column)
  public function add_column($column)
  {
    $this->columns[] = $column;
  }

	/**
	 *Available parameters:
	 *no_edit = only view
	 *edit    = only edit
	 *append  = used to manage n2n tables (see function set_n2n_infos for further information)
	 *full    = edit, add, remove
	 *remove  = only remove
	 *edit_remove = edit and remove
	 */
  public function set_edit_mode($edit_mode)
  {
    switch($edit_mode)
    {
      case 'no_edit':
        $this->edit_mode = 'no_edit'; break;
      case 'edit':
        $this->edit_mode = 'edit'; break;
      case 'append':
        $this->edit_mode = 'append'; break;
      case 'full':
        $this->edit_mode = 'full'; break;
      case 'remove':
        $this->edit_mode = 'remove'; break;
      case 'edit_remove':
        $this->edit_mode = 'edit_remove'; break;
    }
  }

  public function delete()
  {
    $this->db->sql_query("DELETE FROM $this->sql_table WHERE $this->id_column='$_POST[id]'");
  }

  public function multi_delete()
  {
    $id_column = $this->id_column;
		$this->db->sql_query($this->get_sql_for_list());
		$db2 = clone($this->db);
		while($d = $this->db->get_next_res())
		{
			$db2->sql_query("DELETE FROM $this->sql_table WHERE $this->id_column='".$d->$id_column."'");
		}
  }

  public function date2iso($datestring)
  {
		$datestring = trim($datestring);
		$timestring = null;
		$t=null;

		//sepeate Date from time
		if(strpos($datestring," ")!==FALSE)
		{
			$timestring = substr($datestring,strpos($datestring," ")+1);
			$datestring = substr($datestring,0,strpos($datestring," "));
		}

		if ( preg_match('|\.|', $datestring) )
		{
		   // date is in form DD.MM.YYYY or D.M.YY
		   $date = explode('.', $datestring);
		   if ( strlen($date[2]) == 2 )
		   {
		       // no century givven, so we take the current
		       $date[2] = "20". $date[2];
		   }
		   $day = $date[0];
		   $month = $date[1];
		   $year = $date[2];
		}
		elseif ( preg_match('|\/|', $datestring) )
		{
		   // date is in form m/d/y
		   $date = explode('/', $datestring);
		   if ( strlen($date[2]) == 2 )
		   {
		       // no century givven, so we take the current
		       $date[2] = "20" . $date[2];
		   }
		   $day = $date[1];
		   $month = $date[0];
		   $year = $date[2];
		}
		elseif ( preg_match('|\-|', $datestring) )
		{
		   // date is in form YYYY-MM-DD
		   $date = explode('-', $datestring);
		   if ( strlen($date[0]) == 2 )
		   {
		       // no century givven, so we take the current
		       $date[0] = "20" . $date[0];
		   }
		   $day = $date[2];
		   $month = $date[1];
		   $year = $date[0];
		}
		else
		{
		   return false;
		}

		$datestring = sprintf("%04d-%02d-%02d", $year, $month, $day)." ".$timestring;
		return trim($datestring);
  }

  public function multi_save()
  {
    $id_column = $this->id_column;
    foreach($this->columns as $col)
    {
			if($_GET['field']==$col->get_save_column()) { break; }
		}
		$this->db->sql_query($this->get_sql_for_list());
		$db2 = clone($this->db);
		$msgs = "";
		while($data = $this->db->get_next_res())
		{
	    $txt='';
	    if(isset($_POST[$col->get_save_column()]) && $col->get_edit_typ()!='not_editable')
	    {
	      $element = $_POST[$col->get_save_column()];
	      if($col->get_edit_typ() =='date') { $element = $this->date2iso($element); }
	      if($col->get_edit_typ() =='checkbox') { if($element=='on') { $element = '1'; } else { $element = '1'; }}
	      $prefix = '';
				if(trim($element)!='')
				{
					//Escape single quotes for SQL
					$element = str_replace("'","''",$element);
				}
				$txt.= $col->get_save_column()."=".$prefix."'".$element."'";
	    }
	    else
	    {
	      if($col->get_edit_typ() =='checkbox') //Checkboxes don't have a POST entry if they are unchecked
	      {
	        $element = '0';
	        $txt.= $col->get_save_column()."='".$element."',";
	      }
	    }
			$db2->sql_query("UPDATE ".$this->sql_table." SET ".$txt." WHERE ".$this->id_column." = '".$data->$id_column."'");
		}
    return null;
	}

  public function save()
  {
    //Save row
    if($_POST['id']!='0')
    {
      $txt='';
      foreach($this->columns as $col)
      {
        if(isset($_POST[$col->get_save_column()]) && $col->get_edit_typ()!='not_editable')
        {
          $element = $_POST[$col->get_save_column()];
          if($col->get_edit_typ() =='date') { $element = $this->date2iso($element); }
          if($col->get_edit_typ() =='datetime') { $element = $this->date2iso($element); }
          if($col->get_edit_typ() =='checkbox') { $element = '1'; }
          $prefix = '';
					if(trim($element)!='')
					{
						//Escape single quotes for SQL
						$element = str_replace("'","''",$element);
					}
					if($element=='NULL')
					{
						$txt.= $col->get_save_column()."=".$prefix."NULL,";
					}
					else
					{
						$txt.= $col->get_save_column()."=".$prefix."'".$element."',";
					}
        }
        else
        {
          if($col->get_edit_typ() =='checkbox' && $col->show_on_edit!==false) //Checkboxes don't have a POST entry if they are unchecked
          {
            $element = '0';
            $txt.= $col->get_save_column()."='".$element."',";
          }
        }
      }
      if(substr($txt,strlen($txt)-1) == ",") { $txt = substr($txt,0,strlen($txt)-1); }
      //throw new exception("UPDATE $this->sql_table SET $txt WHERE $this->id_column='$_POST[id]'");
      if($txt!='') { return $this->db->sql_query("UPDATE $this->sql_table SET $txt WHERE $this->id_column=:id",array('id'=>$_POST['id'])); }
    }
    else
    //Copy/New row
    {
      $fields='';
      $vals='';

      foreach($this->columns as $col)
      {
        if(isset($_POST[$col->get_save_column()]) && $col->get_edit_typ()!='not_editable')
        {
          $element = $_POST[$col->get_save_column()];
          if($col->get_edit_typ() =='date') { $element = $this->date2iso($element); }
          if($col->get_edit_typ() =='datetime') { $element = $this->date2iso($element); }
          if($col->get_edit_typ() =='checkbox') { if($element=='on') { $element = '1'; } else { $element = '1'; }}
          $fields.=$col->get_save_column().",";
					//Escape single quotes for SQL
					$element = str_replace("'","''",$element);

          $vals.= "'".$element."',";
        }
      }
      if(substr($fields,strlen($fields)-1) == ",") { $fields = substr($fields,0,strlen($fields)-1); }
      if(substr($vals,strlen($vals)-1) == ",") { $vals = substr($vals,0,strlen($vals)-1); }
      if($fields!='') { return $this->db->sql_query("INSERT INTO $this->sql_table ($fields) VALUES ($vals)"); }
    }
  }

  public function get_delete_permission()
  {
    $txt = "<div style='border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg_red.png\");overflow:hidden;margin:auto;padding:10px;font-size:16pt;'>
              Soll dieser Datensatz wirklich gelöscht werden?<p/>
              <span style='color:red;font-size:16pt;cursor:pointer;'
                onClick=\"$('#edit_message').fadeTo(500,0,function()
                {
                  $('#query_edit').fadeTo(200,0);
                  var data = 'id='+$_GET[id];
                  $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=deleting',
                  function()
                  {
										set_parameter('ajax','delete');
                    $.ajax({
                      type: 'POST',
                      url: '$_SERVER[PHP_SELF]?' + parameters,
                      data: data,
                      success: function(response)
                      {
                        $('#query_edit').css('display','none');
                        if(response.trim()!='')
                        {
                           $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=error_deleting&error=', {error: response},
                           function()
                           {
                             $('#close').click(function()
                             {
                              $('#edit_message').fadeTo(500,0,function()
                              {
                                $('#edit_message').css('display','none');
                                unmark_row();
                              });
                            });
                           $('#edit_message').fadeTo(500,1)
                           });
                        }
                        else
                        {
                          $('#edit_message').fadeTo(500,1,
                          function ()
                          {
                            set_parameter('ajax','');
                            set_parameter('id','');
                            $('#query_footer').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_footer');
                            $('#query').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_query',
                            function()
                            {
                              initialize();
                              $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=deleted',
                              function()
                              {
                                $('#edit_message').delay(500).fadeTo(500,0,
                                function ()
                                {
                                  $('#edit_message').css('display','none');";

      if($this->get_reload()) { $txt.= "window.location.reload();"; }
      $txt.="                   });
                              });
                            });
                          });
                        }
                      }
                    });
                  });
                });
                \">Ja</span>
              <span style='color:green;font-size:16pt;cursor:pointer;'
                    onClick=\"
                      $('#edit_message').fadeTo(500,0,function() { $('#edit_message').css('display','none'); unmark_row(); } );\">Nein</span>
            </div>";
    return $txt;
  }

	public function get_multi_delete_permission()
	{
    $txt = "";
    $this->db->sql_query($this->get_sql_for_list());
    $txt = "<div style='border-top:2px solid #666;border-bottom:2px solid #666;background-image: url(\"../inc/imgs/query/bg_red.png\");overflow:hidden;margin:auto;padding:10px;font-size:16pt;'>
							<table style='width:100%;'>
								<tr>
									<td><img src='".level."inc/imgs/query/attention.png' alt='Achtung'/></td>
									<td style='text-align:center;'>
			    					Sollen diese &nbsp;<b style='font-size:24pt;'>".$this->db->count()."</b> &nbsp;Datensätze wirklich gelöscht werden?<p/>
										<span style='color:red;font-size:16pt;cursor:pointer;' onClick=\"
										$('#edit_message').load('$_SERVER[PHP_SELF]?ajax=deleting',
										function()
			              {
											set_parameter('ajax','multi_delete');
			                $.ajax({
			                  type: 'POST',
			                  url: '$_SERVER[PHP_SELF]?' + parameters,
			                  success: function(response)
			                  {
			                    if(response.trim()!='')
			                    {
			                      $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=error_deleting&error=', {error: response},
			                      function()
			                      {
			                      	$('#close').click(function()
			                    		{
																$('#edit_message').fadeTo(500,0,function()
			                        	{
			                          	$('#edit_message').css('display','none');
			                        	});
			                      	});
			                      });
			                    }
			                    else
			                    {
			                      set_parameter('ajax','');
			                      set_parameter('id','');
			                      $('#query_footer').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_footer');
			                      $('#query').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_query',
			                      function()
			                      {
                           initialize();
			                        $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=deleted',
			                        function()
			                        {
			                          $('#edit_message').delay(500).fadeTo(500,0,
			                          function ()
			                          {
			                            $('#edit_message').css('display','none');
																});
			                        });
			                      });
			                    }
			                  }
			                });
			              });
										\">Ja</span>
			              <span style='color:green;font-size:16pt;cursor:pointer;' onClick=\"
											set_parameter('ajax','');
											set_parameter('id','');
											$('#edit_message').fadeTo(500,0,function() { $('#edit_message').css('display','none'); } );
										\">Nein</span>
									</td>
									<td style='text-align:right;'><img src='".level."inc/imgs/query/attention.png' alt='Achtung'/></td>
								</tr>
							</table>
						</div>";
    return $txt;
	}

  public function get_multi_edit()
  {
    $txt = "";
    $this->db->sql_query($this->get_sql_for_list());
    if(!isset($_GET['filter'])) { $_GET['filter'] = ''; }
    $txt.= "  <form method='post' name='detail' id='edit_form'>";
		$txt.= "  <table style='width:100%;'>
								<tr>
									<td><img src='".level."inc/imgs/query/attention.png' alt='Achtung'/></td>
									<td style='text-align:center;'>";
    $txt.= "  			<span style='font-weight:bold;color:red;font-size:12pt;padding-bottom:10px;'>Der eingestellte Wert wird beim speichern auf alle &nbsp; <b style='font-size:24pt;'>".$this->db->count()."</b>&nbsp; Datensätze der Liste angewendet!</span><p/>";
    $txt.= "  			<table style='margin:auto;'>
											<tr>
												<td><b>Feld auswählen</b></td>";
    if(isset($_GET['field'])) {  $txt.= "  		<td><b>Wert eingeben</b><td>"; }

		$txt.= "					</tr>
											<tr>
												<td>";
		$txt.= "						<select name='field' id='field' onchange=\"
                				set_parameter('field',$('#field').val());
                				$('#query_edit').load('$_SERVER[PHP_SELF]?' + parameters);\">";
    $txt.= "  						<option value=''>-- Bitte Feld auswählen --</option>";
    foreach($this->columns as $col)
    {
      if($col->get_edit_typ()!='not_editable' && $col->show_on_edit && $col->get_edit_typ()!='hidden')
      {
        $txt.= "<option ";
        if(isset($_GET['field']) && $_GET['field']==$col->get_save_column()) { $txt.= " selected='1'"; }
        $txt.= " value='".$col->get_save_column()."'>$col->col_name</option>";
      }
    }
    $txt.= "  				</select>
											</td>";

    if(isset($_GET['field']))
		{
      $txt.= " 		 		<td>
												<form method='post' action='' name='multiple_save' id='edit_form'>";
      foreach($this->columns as $col)
      {
        if($col->get_save_column()==$_GET['field']) { break; }
      }
      $alles_gleich = "1";
			$last_value = "";
      while($data=$this->db->get_next_res())
      {
	      if(array_key_exists($col->get_save_column(),$data)) { $name = $col->get_save_column(); }
	      if(array_key_exists($col->db_col_name,$data)) { $name = $col->db_col_name; }
        if($last_value!=$data->$name && $last_value!='') { $alles_gleich='0'; break; }
        $last_value = $data->$name;
      }
      if($alles_gleich!='1') { $last_value=''; }

			$this->db->seek(0);

      if($col->get_selection())
      {
        $selection = $col->get_selection();
        $txt.= "<select name='".$col->get_save_column()."'>";
        foreach ($selection as $aw)
        {
          $txt.= "<option";
          if($aw['value']==$last_value) { $txt.= " selected"; }
          $txt.= " value='$aw[value]'>$aw[display]</option>";
        }
        $txt.= "</select>";
      }
      else
      {
        $db_col_name = $col->db_col_name;
	      switch($col->get_edit_typ())
	      {
	        case 'checkbox':
	          if(isset($d) && $d->$db_col_name==1) { $val = "checked='checked'"; } else { $val = ''; }
	          $txt.= "<input type='checkbox' ".$val." name='".$col->db_col_name."' style='width:'".$col->get_width()."px;'/></td>";
	          break;
	        case 'area':
	          $txt.= "<textarea name='".$col->db_col_name."' style='width:".$col->get_width()*1.2."px;height:".$col->get_height()."px;'>".$d->$db_col_name."</textarea>";
	          break;
	        case 'date':
	          $txt.= "<input type='text' id='".$col->get_save_column()."' name='".$col->get_save_column()."' value='".$last_value."' style='width:".$col->get_width()*0.9."px;'/></td>";
	          $txt.= "<script type='text/javascript'>
	              Calendar.setup({
	                  inputField     :    '".$col->get_save_column()."',   // id of the input field
	                  ifFormat       :    '%d.%m.%Y',       // format of the input field
	                  showsTime      :    false,
	                  timeFormat     :    '24',
	                  onUpdate       :    ''
	              });
	          </script>";
	          break;

	        case 'datetime':
	          $txt.= "<input type='text' id='".$col->get_save_column()."' name='".$col->get_save_column()."' value='".$last_value."' style='width:".$col->get_width()*0.9."px;'/></td>";
	          $txt.= "<script type='text/javascript'>
	              Calendar.setup({
	                  inputField     :    '".$col->get_save_column()."',   // id of the input field
	                  ifFormat       :    '%d.%m.%Y %H:%M',       // format of the input field
	                  showsTime      :    true,
	                  timeFormat     :    '24',
	                  onUpdate       :    ''
	              });
	          </script>";
	          break;

	        default:
            if($alles_gleich=='1') { $val = $last_value; } else { $val = "(mehrere Werte)"; }
						$val = str_replace("'","&#39;",$val);
	          $txt.= "<input type='text' name='".$col->db_col_name."' value='".$val."' style='width:".$col->get_width()*0.9."px;'/>";
	      }
			}
      $txt.= " 	 			</form>
										</td>";
  	}
		$txt.= "			</tr>
								</table>";

    $txt.= "  </td>
							<td style='text-align:right;'><img src='".level."inc/imgs/query/attention.png' alt='Achtung'/></td>";
  	$txt.= "</table>";
	  $txt.= "<div style='clear:both;'>\n";
    //CLOSE************************************************
    $txt.= "<img style='margin:5px;vertical-align:middle;cursor:pointer;'
              onClick=\"$('#query_edit').fadeTo(500,0,function()
              {
                set_parameter('ajax','');
                set_parameter('id','');
                unmark_row();
                $('#query_edit').css('display','none');
              });\" src='".level."inc/imgs/query/pfeil_oben.gif' title='Schliessen'/>";
    //SAVE************************************************
    if(isset($_GET['field']))
		{
		$txt.= "<img style='margin:5px;vertical-align:middle;cursor:pointer;'
              onClick=\"
                $('#query_edit').fadeTo(200,0);
                $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
                function()
                {
                  var data = $('#edit_form').serialize();
									set_parameter('ajax','multi_save');
                  $.ajax({
                      type: 'POST',
                      url: '$_SERVER[PHP_SELF]?' + parameters,
                      data: data,
                      success: function(response)
                      {
                        $('#query_edit').css('display','none');
                        if(response.trim()!='')
                        {
                           $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=error_saving&error=', {error: response},
                           function()
                           {
                             $('#close').click(function()
                             {
                              $('#edit_message').fadeTo(500,0,function()
                              {
                                $('#edit_message').css('display','none');
                                unmark_row();
                              });
                            });
                            $('#edit_message').fadeTo(500,1)
                          });
                        }
                        else
                        {
                          $('#edit_message').fadeTo(500,1,
                          function ()
                          {
                            set_parameter('ajax','');
                            set_parameter('id','');
                            $('#query').load('$_SERVER[PHP_SELF]?' + parameters +' #replace_query',
                            function()
                            {
                              initialize();
                              $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saved',
                              function()
                              {
                                $('#edit_message').delay(500).fadeTo(500,0,
                                function ()
                                {
                                  unmark_row();
                                  $('#edit_message').css('display','none');
                                });
                              });
                            }
                            );
                          }
                          )
                        }
                      }
                  });
                }
                );

              \"
             src='".level."inc/imgs/query/save_big.png' title='Speichern'/>";
		}
    $txt.= "</div>";
    return $txt;
  }

  public function get_edit($mode ='edit')
  {
    $txt = "";
    if(!isset($_GET['filter'])) { $_GET['filter'] = ''; }
    $txt.= "  <form method='post' name='detail' id='edit_form'>";
    if(isset($_GET['id']))
    {
      if($mode=='edit') { $txt.="<input type='hidden' name='id' value='$_GET[id]'/>"; } else { $txt.="<input type='hidden' name='id' value='0'/>"; }
			//Load data
			if($_GET['id']!='0')
			{
				$test_str = substr($this->get_sql_select(),strpos($this->get_sql_select(),'FROM '.$this->get_sql_table())); //GROUP BY in the select part is not relevant for HAVING or WHERE
				if(strpos(strtoupper($test_str)," GROUP ")===FALSE)
				{
        	$sql_cmd = $this->get_sql_select()." WHERE ".$this->id_column."='".$_GET['id']."'";
				}
				else
				{
        	$sql_cmd = $this->get_sql_select()." HAVING ".$this->id_column."='".$_GET['id']."'";
				}
				$d = $this->db->sql_query_with_fetch($sql_cmd);
			} else {$d = null;}

      foreach($this->columns as $col)
      {
        $db_col_name = $col->db_col_name;
        $get_save_column = $col->get_save_column();
        if($col->get_edit_typ()!='not_editable' && $col->show_on_edit)
        {
          if($col->get_edit_typ()!='hidden') { $txt.= "<span style='min-height:40px;float:left;margin:5px;'><b>".$col->col_name."</b><br>"; }
          if($col->get_selection())
          {
            $selection = $col->get_selection();
            $txt.= "<select name='".$col->get_save_column()."'>";
            foreach ($selection as $aw)
            {
              $txt.= "<option";
              if($d!=null) { if($aw['value']==$d->$get_save_column) { $txt.= " selected"; } }
              $txt.= " value='$aw[value]'>$aw[display]</option>";
            }
            $txt.= "</select>";
          }
          else
          {
            switch($col->get_edit_typ())
            {
              case 'checkbox':
                if($d->$db_col_name==1) { $val = "checked='checked'"; } else { $val = ''; }
                if($d === null) { if($col->get_default_value()==1 OR $col->get_default_value()=='on') { $val = "checked='checked'"; } else { $val = ''; } }
                $txt.= "<input type='checkbox' ".$val." name='".$col->db_col_name."' style='width:'".$col->get_width()."px;' ".$col->get_javascript()."/></td>";
                break;
              case 'area':
								if($d === null) { if($col->get_default_value()) { $val = $col->get_default_value(); } else { $val = ''; } } else { $val = $d->$db_col_name; }
                $txt.= "<textarea name='".$col->db_col_name."' style='width:".$col->get_width()*1.2."px;height:".$col->get_height()."px;'>".$val."</textarea>";
                break;
              case 'date':
								if($d === null) { if($col->get_default_value()) { $val = $col->get_default_value(); } else { $val = ''; } } else { $val = $d->$db_col_name; }
                $txt.= "<input type='text' id='".$col->get_save_column()."' name='".$col->get_save_column()."' value='".$val."' style='width:".$col->get_width()*0.9."px;'/></td>";
                $txt.= "<script type='text/javascript'>
                    Calendar.setup({
                        inputField     :    '".$col->get_save_column()."',   // id of the input field
                        ifFormat       :    '%d.%m.%Y',       // format of the input field
                        showsTime      :    false,
                        timeFormat     :    '24',
                        onUpdate       :    ''
                    });
                </script>";
                break;
              case 'datetime':
								if($d === null) { if($col->get_default_value()) { $val = $col->get_default_value(); } else { $val = ''; } } else { $val = $d->$db_col_name; }
                $txt.= "<input type='text' id='".$col->get_save_column()."' name='".$col->get_save_column()."' value='".$val."' style='width:".$col->get_width()*0.9."px;'/></td>";
                $txt.= "<script type='text/javascript'>
                    Calendar.setup({
                        inputField     :    '".$col->get_save_column()."',   // id of the input field
                        ifFormat       :    '%d.%m.%Y %H:%M',       // format of the input field
                        showsTime      :    true,
                        timeFormat     :    '24',
                        onUpdate       :    ''
                    });
                </script>";
                break;

							case 'hidden':
								if($d === null) { if($col->get_default_value()) { $val = $col->get_default_value(); } else { $val = ''; } } else { $val = $d->$db_col_name; }
								$val = str_replace("'","&#39;",$val);
              	$txt.="<input type='hidden' name='".$col->db_col_name."' value='".$val."'/>";
								break;

              default:
								if($d === null) { if($col->get_default_value()) { $val = $col->get_default_value(); } else { $val = ''; } } else {  $val = $d->$db_col_name; }
								$val = str_replace("'","&#39;",$val);
                $txt.= "<input type='text' name='".$col->db_col_name."' value='".$val."' style='width:".$col->get_width()*0.9."px;' ".$col->get_javascript()." />";
            }
          }
          $txt.= "</span>";
        }
        else
        {
					if($col->show_on_edit)
					{
            $txt.= "<div style='float:left;margin:5px 5px 5px 5px;'><span><b>".$col->col_name."</b></span><br>";
            $txt.= "<span style='margin-top:2px;display:block;'>".$d->$db_col_name."</span></div>";
            $txt.= "</span>";
					}
        }
      }
    }
    $txt.= "</form>";
    $txt.= "<div style='clear:both;'>\n";
    //CLOSE************************************************
    $txt.= "<img style='margin:5px;vertical-align:middle;cursor:pointer;'
              onClick=\"$('#query_edit').fadeTo(500,0,function()
              {
                set_parameter('ajax','');
                set_parameter('id','');
                unmark_row();
                $('#query_edit').css('display','none');
              });\" src='".level."inc/imgs/query/pfeil_oben.gif' title='Schliessen'/>";
    //SAVE************************************************
    $txt.= "<img style='margin:5px;vertical-align:middle;cursor:pointer;'
              onClick=\"
                $('#query_edit').fadeTo(200,0);
                $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
                function()
                {
                  var data = $('#edit_form').serialize();
	                set_parameter('ajax','save');
                  $.ajax({
                      type: 'POST',
                      url: '$_SERVER[PHP_SELF]?' + parameters,
                      data: data,
                      success: function(response)
                      {
                        $('#query_edit').css('display','none');
                        if(response.trim()!='')
                        {
                           $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=error_saving&error=', {error: response},
                           function()
                           {
                             $('#close').click(function()
                             {
                              $('#edit_message').fadeTo(500,0,function()
                              {
                                $('#edit_message').css('display','none');
                                $('#query_edit').fadeTo(200,1);
                              });
                            });
                            $('#edit_message').fadeTo(500,1)
                          });
                        }
                        else
                        {
                          $('#edit_message').fadeTo(300,1,
                          function ()
                          {
                            set_parameter('ajax','');
                            set_parameter('id','');
                            $('#query').load('$_SERVER[PHP_SELF]?' + parameters +' #replace_query',
                            function()
                            {
                              initialize();
                              $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saved',
                              function()
                              {
                                $('#edit_message').delay(500).fadeTo(200,0,
                                function ()
                                {
                                  unmark_row();
                                  $('#edit_message').css('display','none');
                                });
                              });
                            }
                            );
                          }
                          )
                        }
                      }
                  });
                }
                );

              \"
             src='".level."inc/imgs/query/save_big.png' title='Speichern'/>";
		$txt.= "<span style='color:#FFF;'>".$_GET['id']."</span>";
    $txt.= "</div>";
    return $txt;
  }

  public function set_sql_select($sql)
  {
    $this->sql_select = $sql;
  }

  public function get_sql_select()
  {
    if(isset($this->sql_select))
    {
      return $this->sql_select;
    }
    else
    {
			return "SELECT * FROM ".$this->sql_table;
    }
  }

  public function set_n2n_infos($table,$own_ID_col,$target_ID_col, $target_ID,$fix_col=null,$fix_val=null)
  {
    $this->n2n_table = $table;
		$this->n2n_id_col = $own_ID_col;
		$this->n2n_target_id_col = $target_ID_col;
		$this->n2n_target_id_val = $target_ID;

		if($fix_col!=null) { $this->n2n_fix_col = $fix_col; }
		if($fix_val!=null) { $this->n2n_fix_val = $fix_val; }

		if($fix_col!=null)
		{
			$this->append_sql_string = "INSERT INTO ".$table." ($own_ID_col,$target_ID_col,$fix_col) VALUES ('XXX_ID','$target_ID','$fix_val')";
		}
		else
		{
			$this->append_sql_string = "INSERT INTO ".$table." ($own_ID_col,$target_ID_col) VALUES ('XXX_ID','$target_ID')";
		}

  }

  public function get_n2n_infos($info)
  {
		if($info=='table')
		{
	    if(isset($this->n2n_table))
	    {
	      return $this->n2n_table;
	    }
	    else
	    {
				return null;
	    }
		}
  }

  public function get_list()
  {
    //Check for necessary definitions to create query
    if(!$this->get_sql_table()) { throw new exception("<span style='font-size:16pt;'>No SQL Table defined.</span> <p/><i>Use function <b>\"set_sql_table\"</b> from class <b>\"class_query\"</b> to define it."); }
    if(!$this->get_id_column()) { throw new exception("<span style='font-size:16pt;'>No ID column defined.</span> <p/><i>Use function <b>\"set_id_column\"</b> from class <b>\"class_query\"</b> to define it or set the primary key in the sql table <b>\"".$this->get_sql_table()."\"</b>"); }
		if(!$this->save_filters_in_session) { $_SESSION['curr_where_string'] = null; $_SESSION['curr_filters'] = null; }

    $this->tbl_width = $this->width;
    $this->edit_width = $this->width*0.9;
    $this->edit_margin = $this->width*0.05-10;

    $txt = "";
    if(!IS_AJAX)
    {
      $myPage = new page(clone $this->db);
      $tbl_width=200;
      $has_users = false;
			$mySQL = $this->get_sql_for_list();
			if(strpos(strtolower($mySQL),'user_email')!==false OR strpos(strtolower($mySQL),' users ')!==false) { $has_users=true; }
      foreach($this->columns as $col)
      {
        $tbl_width = $tbl_width+$col->get_width();
      }
      $txt.= "  <div id='edit_message' class='draggable ui-widget-content'></div>\n";
      $txt.= "  <div id='query_edit' class='draggable ui-widget-content'></div>\n";

      $txt.= "  <div id='more' style='margin-top:0px;padding:10px;border:3px outset #DDD;background-color:white;display:none;position:absolute;border-radius:20px;'>";
      $helper = new helper();
      if($helper->get_browser_name() == 'Internet Explorer') { $txt.= "  <img id='btn_clipboard' style='cursor:pointer;' src='".level."inc/imgs/query/clipboard.png' alt='Export in Zwischenablage' title='Export in Zwischenablage'/>\n"; }
      $txt.= "  <img id='btn_excel' style='cursor:pointer;' src='".level."inc/imgs/query/excel.png' alt='Export nach Excel' title='Export nach Excel'/>\n";
      if($has_users) { $txt.= "  <img id='btn_mail' style='cursor:pointer;' src='".level."inc/imgs/query/mail.png' alt='Mail senden' title='Mail senden'/>\n"; }
      if($this->edit_mode == 'append')
			{
				if(isset($_GET['only_checked']))
				{
           if($_GET['only_checked']=='1')
					 {
							$txt.= "  <img id='btn_only_checked' style='cursor:pointer;' src='".level."inc/imgs/query/checkbox.png' alt='Nur NICHT Zugewiesene zeigen' title='Nur NICHT Zugewiesene zeigen'/>\n";
					 }
					 else
					 {
							$txt.= "  <img id='btn_only_checked' style='cursor:pointer;' src='".level."inc/imgs/query/checkbox_unchecked.png' alt='Alle zeigen' title='Alle zeigen'/>\n";
					 }
				}
				else
				{
					$txt.= "  <img id='btn_only_checked' style='cursor:pointer;' src='".level."inc/imgs/query/checkbox_neutral.png' alt='Nur Zugewiesene zeigen' title='Nur Zugewiesene zeigen'/>\n";
				}
			}
	    if(isset($_SESSION['login_user']))
	    {
		    switch($this->edit_mode)
		    {
		      case 'full':
	          if($_SESSION['login_user']->check_permission($myPage->get_path(),"write")) { $txt.= "  <img id='btn_multi_edit' style='cursor:pointer;' src='".level."inc/imgs/query/multi_edit.png' alt='Mehrfach Editieren' title='Mehrfach Editieren'/>\n"; }
	          if($_SESSION['login_user']->check_permission($myPage->get_path(),"delete")) { $txt.= "  <img id='btn_multi_delete' style='cursor:pointer;' src='".level."inc/imgs/query/multi_delete.png' alt='Mehrfach Löschen' title='Mehrfach Löschen'/>\n"; }
		        break;

		      case 'edit_remove':
	          if($_SESSION['login_user']->check_permission($myPage->get_path(),"write")) { $txt.= "  <img id='btn_multi_edit' style='cursor:pointer;' src='".level."inc/imgs/query/multi_edit.png' alt='Mehrfach Editieren' title='Mehrfach Editieren'/>\n"; }
	          if($_SESSION['login_user']->check_permission($myPage->get_path(),"delete")) { $txt.= "  <img id='btn_multi_delete' style='cursor:pointer;' src='".level."inc/imgs/query/multi_delete.png' alt='Mehrfach Löschen' title='Mehrfach Löschen'/>\n"; }
		        break;

		      case 'edit':
	          if($_SESSION['login_user']->check_permission($myPage->get_path(),"write")) { $txt.= "  <img id='btn_multi_edit' style='cursor:pointer;' src='".level."inc/imgs/query/multi_edit.png' alt='Mehrfach Editieren' title='Mehrfach Editieren'/>\n"; }
		        break;

		      case 'remove':
	          if($_SESSION['login_user']->check_permission($myPage->get_path(),"delete")) { $txt.= "  <img id='btn_multi_delete' style='cursor:pointer;' src='".level."inc/imgs/query/multi_delete.png' alt='Mehrfach Löschen' title='Mehrfach Löschen'/>\n"; }
		    }
	    }

//       $txt.= "  <img id='btn_import' style='cursor:pointer;' src='".level."inc/imgs/query/import.png' alt='Import' title='Import'/>\n";
      $txt.= "  </div>\n";
    }
    $myPage = new page(clone $this->db);
      $txt.= "  <div id='query_header' style='width:".$this->tbl_width."px;'>\n";
	    $txt.= "  <div id='replace_header'>\n";
      $txt.= "    <table id='tbl_query_header' style='width:".$this->tbl_width."px;' border='0' cellspacing='1'>\n";
      $txt.= "      <tr>\n";

      //*********************************************************************************************
      // Header-line
      //---------------------------------------------------------------------------------------------
      $txt.= "          <td class='filter_tds' style='vertical-align:bottom;width:10px;'><img id='show_more' style='cursor:pointer;' src='".level."inc/imgs/query/more.png' alt='Weitere Funktionen'/></td>\n";
      //Add Button
      if($this->edit_mode == 'full' AND isset($_SESSION['login_user']) AND $_SESSION['login_user']->check_permission($myPage->get_path(),"delete") )
      {
        $txt.= "          <td class='filter_tds' style='vertical-align:bottom;width:10px;'>";
        $txt.= "            <img style='cursor:pointer;' onClick=\"new_row();\" src='".level."inc/imgs/query/add.png' alt='Hinzufügen' title='Hinzufügen'/>";
        $txt.= "          </td>\n";
	      $txt.= "          <td  class='filter_tds'  style='vertical-align:bottom;width:10px;'>&nbsp;</td>\n";
      }
      else
      {
				if($this->edit_mode != 'append')
				{
	        $txt.= "          <td class='filter_tds' style='vertical-align:bottom;width:10px;'>";
	        $txt.= "            <img src='".level."inc/imgs/query/add_inactiv.png' alt='Hinzufügen' title='Hinzufügen'/>";
	        $txt.= "          </td>\n";
		      $txt.= "          <td  class='filter_tds'  style='vertical-align:bottom;width:10px;'>&nbsp;</td>\n";
				}
				else
				{
				}
      }
  
      $filter_string = "";
      //---------------------------------------------------------------------------------------------
      //Load filter parameter from page parameters
	    if(isset($_GET['filter']) && $_GET['filter']!='') { $f_werte = explode(';',$_GET['filter']); }
			else
			{
		    if(isset($_SESSION['curr_filters']) && $_SESSION['curr_filters']!='') { $f_werte = explode(';',$_SESSION['curr_filters']); } else { $f_werte = null; }
			}

      $i = 0;
      if(isset($_SESSION['login_user']))
      {
  			$t = new translation(clone $this->db,$_SESSION['login_user']->get_frontend_language());
      }
      else
      {
  			$t = new translation(clone $this->db);
      }

			//Get default sorting
	    if(!isset($_GET['orderBy']) && isset($this->default_order_by)) { $_GET['orderBy'] = $this->default_order_by; }
	    if(!isset($_GET['sortDir']) && isset($this->default_sort_dir)) { $_GET['sortDir'] = $this->default_sort_dir; }

      foreach($this->columns as $col)
      {
				if($col->show_on_list && $col->get_edit_typ()!='hidden')
				{
	        $txt.= "        <td nowrap class='filter_tds' style='width:".$col->get_width()."px;'><span style='cursor:pointer;' onclick='sort(\"".$col->get_sort_column()."\");'>".$t->translate($col->col_name)."</span>";
					if(isset($_GET['orderBy']) && $_GET['orderBy']==$col->get_sort_column())
					{
						if(isset($_GET['sortDir']) && $_GET['sortDir']=='DESC')
						{
			        $txt.= "<img src='".level."inc/imgs/query/sort_desc.png' alt='Absteigend' title='Absteigend'/>";
						}
						else
						{
							$txt.= "<img src='".level."inc/imgs/query/sort_asc.png' alt='Aufsteigend' title='Aufsteigend'/>";
						}
					}
	        $txt.= "<br><input class='filter_fields' type='text' style='width:".$col->get_width()."px;' name='$col->db_col_name' ";
	        if($f_werte !== null) { if(count($f_werte)>$i) { $txt.= "value='$f_werte[$i]' "; } }
	        $filter_string.= ";";
	        $txt.= "/></td>\n";
	        $i++;
				}
      }
      //---------------------------------------------------------------------------------------------
      $txt .= "       <td style='width:15px;'></td>\n"; //scrollbars from content
      $txt .= "      </tr>\n";
      $txt .= "    </table>\n";
	    $txt .= "  </div><!--End replace Query Header-->\n";
      $txt .= "  </div><!--End Query Header-->\n";

    $txt .= "  <div id='query' style='height:100%;'>\n";
    $txt .= "  <div id='replace_query'>\n";
    $txt .= "    <table id='tbl_query_content' border='0' cellspacing='1' >\n";
    //*********************************************************************************************

    //*********************************************************************************************
    // Content
    //---------------------------------------------------------------------------------------------
    $i = 0;
    if(!isset($_GET['start_row'])) { $_GET['start_row'] = 0; }
    $max = $_GET['start_row'] + $this->max_rows;
		$mySQL = $this->get_sql_for_list();

		$oldSQL = $mySQL;
		if($this->debug) { $txt.= $mySQL; }
		//Handle big amount of data
		try { $this->db->sql_query($mySQL); } catch (Exception $e) { return $e->getMessage(); }
		$of = $this->db->count();
    if($_GET['start_row']>=$this->max_rows) { $minus = $_GET['start_row']-$this->max_rows; }
    if($of > $_GET['start_row']+$this->max_rows) { $plus = $_GET['start_row']+$this->max_rows; }

    if ($max>$this->db->count()) { $max = $this->db->count(); }
    $min = $_GET['start_row']+1;
    if(isset($_GET['only_checked'])) { $min = 1; $max = $this->db->count(); }

    //Define Edit/Delete/Copy Buttons
		$btn_txt = "";
    if(isset($_SESSION['login_user']))
    {
	    switch($this->edit_mode)
	    {
	      case 'full':
          if($_SESSION['login_user']->check_permission($myPage->get_path(),"write"))
          {
            //Save
            $btn_txt.= "        <td><img style='cursor:pointer;' class='edit_row' onClick=\"edit_row($(this),'[ID]');\" src='".level."inc/imgs/query/edit.png' alt='Bearbeiten' title='Bearbeiten'/></td>\n";
          }
          else
          {
            $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/edit_inactiv.png' alt='Bearbeiten' title='Bearbeiten'/></td>\n";
          }
          if($_SESSION['login_user']->check_permission($myPage->get_path(),"delete"))
          {
            //Delete
            $btn_txt.= "        <td><img style='cursor:pointer;' class='edit_row' class='del_button' onClick=\"delete_row($(this),'[ID]');\" src='".level."inc/imgs/query/delete.png' alt='Löschen' title='Löschen'/></td>\n";
            //Copy
            $btn_txt.= "        <td><img style='cursor:pointer;' class='edit_row' onClick=\"copy_row($(this),'[ID]');\" src='".level."inc/imgs/query/copy.png' alt='Kopieren' title='Kopieren'/></td>\n";
          }
          else
          {
            $btn_txt.= "        <td><img class='edit_row' class='del_button' src='".level."inc/imgs/query/delete_inactiv.png' alt='Löschen' title='Löschen'/></td>\n";
            $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/copy_inactiv.png' alt='Kopieren' title='Kopieren'/></td>\n";
          }
	        break;
	      case 'edit_remove':
          if($_SESSION['login_user']->check_permission($myPage->get_path(),"write"))
          {
            //Save
            $btn_txt.= "        <td><img style='cursor:pointer;' class='edit_row' onClick=\"edit_row($(this),'[ID]');\" src='".level."inc/imgs/query/edit.png' alt='Bearbeiten' title='Bearbeiten'/></td>\n";
          }
          else
          {
            $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/edit_inactiv.png' alt='Bearbeiten' title='Bearbeiten'/></td>\n";
          }
          if($_SESSION['login_user']->check_permission($myPage->get_path(),"delete"))
          {
            //Delete
            $btn_txt.= "        <td><img style='cursor:pointer;' class='edit_row' class='del_button' onClick=\"delete_row($(this),'[ID]');\" src='".level."inc/imgs/query/delete.png' alt='Löschen' title='Löschen'/></td>\n";
          }
          else
          {
	          $btn_txt.= "        <td><img class='edit_row' class='del_button' src='".level."inc/imgs/query/delete_inactiv.png' alt='Löschen' title='Löschen'/></td>\n";
					}
          $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/copy_inactiv.png' alt='Kopieren' title='Kopieren'/></td>\n";
	        break;
	      case 'edit':
          if($_SESSION['login_user']->check_permission($myPage->get_path(),"write"))
          {
            //Save
            $btn_txt.= "        <td><img style='cursor:pointer;' class='edit_row' onClick=\"edit_row($(this),'[ID]');\" src='".level."inc/imgs/query/edit.png' alt='Bearbeiten' title='Bearbeiten'/></td>\n";
          }
          else
          {
            $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/edit_inactiv.png' alt='Bearbeiten' title='Bearbeiten'/></td>\n";
          }
          $btn_txt.= "        <td><img class='edit_row' class='del_button' src='".level."inc/imgs/query/delete_inactiv.png' alt='Löschen' title='Löschen'/></td>\n";
          $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/copy_inactiv.png' alt='Kopieren' title='Kopieren'/></td>\n";
	        break;
	      case 'append':
          //Append
          //$btn_txt.= "        <td><img style='cursor:pointer;' class='edit_row' class='append_button' onClick=\"append_row($(this),'[ID]');\" src='".level."inc/imgs/query/arrow_left.png' alt='Zuweisen' title='Zuweisen'/></td>\n";
	        break;
	      case 'remove':
          $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/edit_inactiv.png' alt='Bearbeiten' title='Bearbeiten'/></td>\n";
          if($_SESSION['login_user']->check_permission($myPage->get_path(),"delete"))
          {
            //Delete
            $btn_txt.= "        <td><img style='cursor:pointer;' class='edit_row' class='del_button' onClick=\"delete_row($(this),'[ID]');\" src='".level."inc/imgs/query/delete.png' alt='Löschen' title='Löschen'/></td>\n";
          }
          else
          {
	          $btn_txt.= "        <td><img class='edit_row' class='del_button' src='".level."inc/imgs/query/delete_inactiv.png' alt='Löschen' title='Löschen'/></td>\n";
					}
          $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/copy_inactiv.png' alt='Kopieren' title='Kopieren'/></td>\n";
	        break;
	      case 'no_edit':
            $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/edit_inactiv.png' alt='Bearbeiten' title='Bearbeiten'/></td>\n";
            $btn_txt.= "        <td><img class='edit_row' class='del_button' src='".level."inc/imgs/query/delete_inactiv.png' alt='Löschen' title='Löschen'/></td>\n";
            $btn_txt.= "        <td><img class='edit_row' src='".level."inc/imgs/query/copy_inactiv.png' alt='Kopieren' title='Kopieren'/></td>\n";
	        break;
	    }
    }
    else
    {
      $btn_txt.= "        <td></td>\n";
    }

		if($this->db->count()==0)
		{
        $txt.= "<td class='first_row'></td>";
        $txt.= "<td class='first_row'></td>";
        $txt.= "<td class='first_row'></td>";
        foreach($this->columns as $col)
        {
          $txt.= "        <td class='first_row' style='width:".$col->get_width()."px;'>&nbsp</td>\n";
        }
		}
    if(isset($_SESSION['login_user']) && $_SESSION['login_user']->check_permission($myPage->get_path(),"write")) { $addition = ""; } else { $addition = "disabled='1'"; }
    while($d = $this->db->get_next_res())
    {
	    $i++;
      if(!isset($min) OR $min <= $i)
      {
				if($this->edit_mode=='append')
				{
					$db2 = clone $this->db;
          $id_column = $this->id_column;
					$check_str = "SELECT * FROM ".$this->n2n_table." WHERE ".$this->n2n_id_col."='".$d->$id_column."' AND ".$this->n2n_target_id_col."='".$this->n2n_target_id_val."'";
					//if(isset($this->n2n_fix_col)) { $check_str.= " AND ".$this->n2n_fix_col."='".$this->n2n_fix_val."'"; }
					$db2->sql_query($check_str);
					if($db2->count()>0)
					{
          	$btn_txt= "        <td><input type='checkbox' checked='checked' ".$addition." onClick=\"append_row($(this),'[ID]');\"/></td>\n";
						$is_checked = true;
					}
					else
					{
          	$btn_txt= "        <td><input type='checkbox' ".$addition." onClick=\"append_row($(this),'[ID]');\"/></td>\n";
						$is_checked = false;
					}
				}

				if((isset($_GET['only_checked']) && $_GET['only_checked']=='1' && $is_checked===true) OR
						(isset($_GET['only_checked']) && $_GET['only_checked']=='0' && $is_checked===false) OR (!isset($_GET['only_checked'])))
				{
	        if($i>$max) { break; }
	        //Alternating colors for rows (can be defined by properties row1_css and row2_ccs)
	        if(isset($this->row1_css) AND isset($this->row2_css)) { $style=($i % 2) ? "class='".$this->row1_css."'" : "class='".$this->row2_css."'"; } else { $style=($i % 2) ? "style='background-color:#FFF;'" : "style='background-color:#DDD;'";   }


	        $txt .= "      <tr ".$style.">\n";
          $id_col = $this->id_column;
	        if($i==$min OR $i==1)
	        {
						$txt_temp = str_replace('[ID]',$d->$id_col,$btn_txt);
						$txt .= str_replace("<td>","<td class='first_row'>",$txt_temp);
					}
					else
					{
						$txt .= str_replace('[ID]',$d->$id_col,$btn_txt);
					}
	        //---------------------------------------------------------------------------------------------
	        //---------------------------------------------------------------------------------------------
	        //Columns
	        foreach($this->columns as $col)
	        {
						if($col->show_on_list && $col->get_edit_typ()!='hidden')
						{
              $db_col_name = $col->db_col_name;
				      if($col->get_colDbName_for_list()) { $db = $col->get_colDbName_for_list(); } else { $db = $col->db_col_name; }
		          if($col->get_edit_typ()=='checkbox')
		          {
		            if($i==$min OR $i==1)
		            {
		              $txt.= "        <td class='first_row'><input type='checkbox' ";
		              if($d->$db_col_name==true) { $txt.= "checked='1' "; }
		              $txt.= "disabled='1' name='".$d->$db."'/>";
		              $txt.= "        </td>\n";
		            }
		            else
		            {
		              $txt.= "        <td><input type='checkbox' ";
		              if($d->$db_col_name==true) { $txt.= "checked='1' "; }
		              $txt.= "disabled='1' name='".$d->$db."'/>";
		              $txt.= "        </td>\n";
		            }
		          }
		          else
		          {
	              //if HTML tags are used, don't do line breaks
	              if(strpos($d->$db,"<")===FALSE) { $cell_txt = nl2br($d->$db); } else { $cell_txt = $d->$db; }
								if($col->get_link()!='') { $cell_txt = "<a href='".$col->get_link().$cell_txt."' target='_blank'>$cell_txt</a>"; }
		            if($i==$min OR $i==1)
		            {
		              $txt.= "        <td class='first_row'>$cell_txt</td>\n";
		            }
		            else
		            {
		              $txt.= "        <td>$cell_txt</td>\n";
		            }
		          }
						}
	        }
	        //---------------------------------------------------------------------------------------------
	        $txt.= "      </tr>\n";
	      }
				else
				{
					$i--;
				}
			}
    }
    $txt .= "      </table>\n";
    $txt .= "    </div><!--End Replace content-->\n";
    $txt .= "    </div><!--End Query content-->\n";

    if(isset($_GET['only_checked']))
		{
			$of = $i;
			$max = $i;
			$minus = null;
			$plus = null;
		}
// 		else
// 		{
// 			$of = $this->db->count();
// 		}

    $txt .= "    <div id='query_footer'>\n";
    $txt .= "    <div id='replace_footer'>\n";
    $txt .= "      <table border='0' style='margin-top:10px;'>\n";
    $txt .= "        <tr>\n";
    $txt .= "          <td style='width:20px;'>"; if(isset($minus)) { $txt.= "<img style='cursor:pointer;' onclick=\"change_page($minus);\" src='".level."inc/imgs/query/arrow_left.png'/>"; } else { $txt.= "<img src='".level."inc/imgs/query/arrow_left_inactive.png'/>"; } $txt.= "</td>\n";
    $txt .= "          <td style='width:20px;'>"; if(isset($plus)) { $txt.= "<img style='cursor:pointer;'  onclick=\"change_page($plus);\" src='".level."inc/imgs/query/arrow_right.png'/></a>"; } else { $txt.= "<img src='".level."inc/imgs/query/arrow_right_inactive.png'/>"; }  $txt.= "</td>\n";

    if(isset($_GET['only_checked'])) { $of = $i; $max = $i; } 

    switch($max)
    {
      case 0:
        $txt .= "          <td style='font-size:9pt;'>".$t->translate('Keine Datensätze')."</td>\n"; break;
      case 1:
        $txt .= "          <td style='font-size:9pt;'>".$min." - ".$max." ".$t->translate('von')." ".$of." ".$t->translate('Datensatz')."</td>\n"; break;
      default:
        $txt .= "          <td style='font-size:9pt;'>".$min." - ".$max." ".$t->translate('von')." ".$of." ".$t->translate('Datensätzen')."</td>\n"; break;
    }
    $txt .= "        </tr>\n";
    $txt .= "      </table>\n";
    $txt .= "    </div><!--End Replace Footer-->\n";
    $txt .= "    </div><!--End Query Footer-->\n";
    if(!IS_AJAX)
    {

      $txt.="<script>
            var last_color;
            var last_elm;
            var str_filter='';
            var curr_nr;
            var sortDir='ASC';
            var last_order_by='$_GET[orderBy]';
						var hor_scroll_pos=0;
            var parameters='$_SERVER[QUERY_STRING]';

            $(document).ready(function() {
              $('#query').scroll(function () {
                $('#query_header').scrollLeft($('#query').scrollLeft());
								hor_scroll_pos = $('#query_header').scrollLeft();
              });
              $('#query_edit').draggable({ cursor: 'move' });
              $('#edit_message').draggable({ cursor: 'move', cancel: 'table'});
              $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
              function()
              {
                $('#edit_message').fadeTo(300,1);
	              $('#query_header').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_header',
								function()
								{
	                $('#query').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_query',
	                function()
	                {
										$('#query_header').show();
										$('#query').show();
										$('#query_footer').show();

		                $('#query_footer').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_footer');
	                  $('#edit_message').css('display','none');
			              $('.filter_fields').keypress(function(event) { if(event.which == 13) { filter(); } });
										//For scrolling grid, if the header line is scrolled by TAB selection
										$('.filter_fields').focus(function() { hor_scroll_pos = $('#query_header').scrollLeft(); $('#query').scrollLeft(hor_scroll_pos); });
										initialize(true);
	                  $('#query').scrollTop(0);
	                });
								});
							});

              //Copy to Clipboard
              $('#btn_clipboard').click(function()
              {
                $('#more').css('display','none');
                set_parameter('ajax','clipboard');
                $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
                function()
                {
                  $('#edit_message').fadeTo(300,1);
                  $.ajax({ url: '$_SERVER[PHP_SELF]?' + parameters }).done(
                  function(data)
                  {
                    window.clipboardData.setData('Text', data);
                    $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=done',function(){
                      $('#close').click(
                      function()
                      {
                        $('#edit_message').fadeTo(300,0,function()
                        {
                          $('#edit_message').css('display','none');
													set_parameter('ajax','');
                        });
                      });
                    });
                  });
                });
              });
              //Export to Excel
              $('#btn_excel').click(function(e)
              {
                $('#more').css('display','none');
                set_parameter('ajax','export_excel');
                $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
                function()
                {
                  $('#edit_message').fadeTo(300,1);
                  $.ajax({ url: '$_SERVER[PHP_SELF]?' + parameters }).done(
                  function(data)
                  {
                    $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=done_export&filename=' + data,
                    function()
                    {
                      $('#close').click(
                      function()
                      {
                        $('#edit_message').fadeTo(300,0,function()
                        {
                          $('#edit_message').css('display','none');
                        });
                      });
                    });
                  });
                });
              });
              //Mail to users
              $('#btn_mail').click(function(e)
              {
                $('#more').css('display','none');
                set_parameter('ajax','get_mails');
                $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
                function()
                {
                  $('#edit_message').fadeTo(300,1);
                  $.ajax({ url: '$_SERVER[PHP_SELF]?' + parameters }).done(
                  function(data)
                  {
                    window.clipboardData.setData('Text', data);
                    window.open('mailto:&body=Die gewünschten Mail-Adressen befinden sich in der Zwischenablage. (Einfach Ctrl+V drücken)');
                    $('#edit_message').fadeTo(300,0,function()
                    {
                      $('#edit_message').css('display','none');
											set_parameter('ajax','');
                    });
                  });
                });
              });
              //Multi edit
              $('#btn_multi_edit').click(function(e)
              {
                $('#more').css('display','none');
                unmark_row();
                $('#query_edit').css('border-top-color','orange');
                $('#query_edit').css('border-bottom-color','red');
                $('#query_edit').css('border-left-color','orange');
                $('#query_edit').css('border-right-color','red');
                $('#edit_message').css('display','none');
                $('#query_edit').fadeTo(300,0);
                set_parameter('ajax','multi_edit');
                set_parameter('id','0');
                $('#query_edit').load('$_SERVER[PHP_SELF]?' + parameters,
                function()
                {
                  $('#query_edit').fadeTo(300,1);
                });
              });
            });

            //Multi delete
            $('#btn_multi_delete').click(function(e)
            {
              $('#more').css('display','none');
              unmark_row();
              $('#query_edit').css('display','none');
              $('#edit_message').fadeTo(300,0);
              set_parameter('ajax','multi_delete_permission');
              set_parameter('id','');
              $('#edit_message').load('$_SERVER[PHP_SELF]?' + parameters,
              function()
              {
                $('#edit_message').fadeTo(300,1);
              });
            });

            $('#btn_only_checked').click(function(e)
            {
              set_parameter('start_row',0); //Change to first page
              $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
              function()
              {
                $('#edit_message').fadeTo(300,1);
                str_filter='';
                $('.filter_fields').each(function(index) {
                  str_filter = str_filter + $(this).val().trim() + ';';
                });
								str_filter = str_filter.replace(/ /g,'%20');
                set_parameter('filter',str_filter)
								if(get_parameter('only_checked')=='')
								{
									$('#btn_only_checked').attr('title','Nur NICHT zugewiesene zeigen');
									$('#btn_only_checked').attr('alt','Nur NICHT zugewiesene zeigen');
									$('#btn_only_checked').attr('src','".level."inc/imgs/query/checkbox.png');
									set_parameter('only_checked','1');
								}
								else
								{
									if(get_parameter('only_checked')=='1')
									{
										$('#btn_only_checked').attr('title','Alle zeigen');
										$('#btn_only_checked').attr('alt','Alle zeigen');
										$('#btn_only_checked').attr('src','".level."inc/imgs/query/checkbox_unchecked.png');
										set_parameter('only_checked','0');
									}
									else
									{
										if(get_parameter('only_checked')=='0')
										{
											$('#btn_only_checked').attr('title','Nur zugewiesene zeigen');
											$('#btn_only_checked').attr('alt','Nur zugewiesene zeigen');
											$('#btn_only_checked').attr('src','".level."inc/imgs/query/checkbox_neutral.png');
											set_parameter('only_checked','');
										}
									}
								}
                $('#query').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_query',
                function()
                {
	                $('#query_footer').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_footer');
                  $('#edit_message').css('display','none');
                  initialize();
                  $('#query').scrollTop(0);
                });
							});
            });

            function get_parameter(name)
            {
							name = name.replace(/[\[]/,'\\\[').replace(/[\]]/,'\\\]');

							var regexS = '[\\?&]'+name+'=([^&#]*)';
							var regex = new RegExp( regexS );
							var results = regex.exec(parameters);

							if ( results == null )
								return '';
							else
								return results[1];
						}

            function set_parameter(param,val)
            {
              var re = new RegExp('&'+param+'(\\=[^&]*)?(?=&|$)|^'+param+'(\\=[^&]*)?(&|$)','g');
              parameters = parameters.replace(re, '');
              if(val!='') { parameters = parameters + '&' + param + '=' + val}
            }

            function initialize(first_time)
            {
              set_col_width(first_time);
              add_hover();
              $('#show_more').click(
              function()
              {
                $('#more').fadeTo(300,1);
              });
              $('#more').hover(function() {},
              function()
              {
                $('#more').css('display','none');
              });
							//alert(parameters);
            }

            function sort(db_col)
            {
							if(last_order_by == db_col) { if(sortDir=='ASC') { sortDir='DESC'; } else { sortDir='ASC'; } } else { sortDir = 'ASC'; }
              $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
              function()
              {
                $('#edit_message').fadeTo(300,1);
                set_parameter('orderBy',db_col);
                set_parameter('sortDir',sortDir);
                $('#query_header').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_header',
								function()
                {
	                $('#query').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_query',
	                function()
									{
			              $('.filter_fields').keypress(function(event) { if(event.which == 13) { filter(); } });
	                  $('#edit_message').css('display','none');
	                  initialize(true);
	                  $('#query').scrollTop(0);
                    $('#query').scrollLeft(hor_scroll_pos);
									});
                });
              });
							last_order_by = db_col;
            }

            function add_hover()
            {
              $('.edit_row').hover(
                function()
                {
                  if($('#query_edit').css('display')=='none' && $('#edit_message').css('display')=='none')
                  {
                    unmark_row();
                    last_elm = $(this);
                    last_color = $(this).parent().parent().css('background-color');
                    $(this).parent().parent().css('background-color','#BBBBEE'); }
                  },
                function()
                {
                  if($('#query_edit').css('display')=='none' && $('#edit_message').css('display')=='none')
                  {
                    $(this).parent().parent().css('background-color',last_color);
                  }
                }
              );
            }

            function set_col_width(first_time)
            {
              var tbl_width=68; //3x EditButton = 60px; 4x cellpading = 4px; 4x cellspacing = 4px;
							$('.first_row').each(function(index)
              {
								";
								if($this->edit_mode == 'append') { $txt.= "var index_fields = index-1;"; } else { $txt.= "var index_fields = index-3;"; }
                $txt.= "
                var header_w = $('.filter_tds:eq('+index+')').width();
                var content_w = $(this).width();
								if(first_time) { if(header_w > content_w) { col_w = header_w; } else { col_w = content_w; } } else { col_w = header_w; }
								if(index_fields<0) { col_w = 20; } //Set edit columns to a fixed width
								$(this).width(col_w);
                if(first_time)
								{
									$('.filter_tds:eq('+index+')').width(col_w);
	                var w_fields = col_w-5; //Padding cell and input fields
									if(index_fields>-1) { $('.filter_fields:eq('+index_fields+')').width(w_fields); }
	                tbl_width=tbl_width+col_w;
								}
              });
              if(first_time)
							{
	              $('#tbl_query_content').width(tbl_width);
								$('#tbl_query_header').width(tbl_width+17); //Blind cell on the right side to compensate the scrollbar
							} 
							else
							{
	              $('#tbl_query_content').width($('#tbl_query_header').width()-17);
							}
            }

            function new_row()
            {
              unmark_row();
              $('#query_edit').css('border-color','green');
              $('#edit_message').css('display','none');
              $('#query_edit').fadeTo(300,0);
              set_parameter('ajax','edit');
              set_parameter('id','0');
              $('#query_edit').load('$_SERVER[PHP_SELF]?' + parameters,
              function()
              {
                $('#query_edit').fadeTo(300,1);
              });
            }
            function edit_row(elm,id)
            {
              unmark_row();
              mark_row(elm);
              $('#query_edit').css('border-color','#CCC');
              $('#edit_message').css('display','none');
              $('#query_edit').fadeTo(300,0);
              set_parameter('ajax','edit');
              set_parameter('id',id);
              $('#query_edit').load('$_SERVER[PHP_SELF]?'+parameters,
              function()
              {
                $('#query_edit').fadeTo(300,1);
              });
            }

						function append_row(elm,id)
            {
              unmark_row();
              mark_row(elm);
							elm.prop('disabled',true);
              set_parameter('ajax','append');
              set_parameter('id',id);
              ";
							if(isset($_GET['group_id'])) { $txt.="set_parameter('group_id',".$_GET['group_id'].");"; }
							$txt.="
              $('#edit_message').fadeTo(300,1);
              $.ajax({ url: '$_SERVER[PHP_SELF]?' + parameters }).done(
              function(data)
              {
                $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=done_append&data='+data,
								function()
								{
                  $('#edit_message').delay(500).fadeTo(200,0,
                  function ()
                  {
						        set_parameter('ajax','');
						        set_parameter('id','');
                    $('#query').load('$_SERVER[PHP_SELF]?' + parameters +' #replace_query',
										function()
										{
	                    unmark_row();
	                    $('#edit_message').css('display','none');
											elm.prop('disabled',false);
    									set_col_width();
										});
									});
                });
              });
            }
            function delete_row(elm,id)
            {
              unmark_row(last_elm);
              $('#query_edit').css('display','none');
              set_parameter('ajax','delete_permission');
              set_parameter('id',id);
              $('#edit_message').load('$_SERVER[PHP_SELF]?'+parameters,
              function()
              {
                mark_row(elm);
                $('#edit_message').fadeTo(300,1);
              });
            }

            function filter()
            {
              set_parameter('start_row',0); //Change to first page
              $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
              function()
              {
                $('#edit_message').fadeTo(300,1);
                str_filter='';
                $('.filter_fields').each(function(index) {
                  str_filter = str_filter + $(this).val().trim() + ';';
                });
								str_filter = str_filter.replace(/ /g,'%20');
                set_parameter('filter',str_filter);
                $('#query').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_query',
                function()
                {
	                $('#query_footer').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_footer');
                  $('#edit_message').css('display','none');
                  initialize();
                  $('#query').scrollTop(0);
                  $('#query').scrollLeft(hor_scroll_pos);
									set_col_width();
                });
              });
            }

            function copy_row(elm,id)
            {
              unmark_row();
              mark_row(elm);
              $('#query_edit').css('border-color','green');
              $('#edit_message').css('display','none');
              $('#query_edit').fadeTo(300,0);
              set_parameter('ajax','copy');
              set_parameter('id',id);
              $('#query_edit').load('$_SERVER[PHP_SELF]?' + parameters,
              function()
              {
                $('#query_edit').fadeTo(300,1);
              });
            }
            function mark_row(elm)
            {
              last_elm = elm;
              last_color = elm.closest('tr').css('background-color');
              elm.closest('tr').css('background-color','yellow');
            }
            function unmark_row()
            {
              if(last_elm) { last_elm.closest('tr').css('background-color',last_color);}
              last_elm = null;
              last_color = null;
            }
            function change_page(nr)
            {
              curr_nr = nr;
              $('#edit_message').load('$_SERVER[PHP_SELF]?ajax=saving',
              function()
              {
                $('#edit_message').fadeTo(300,1);
                set_parameter('start_row',curr_nr);

                $('#query').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_query',
                function()
                {
	                $('#query_footer').load('$_SERVER[PHP_SELF]?' + parameters + ' #replace_footer');
                  $('#edit_message').css('display','none');
                  initialize();
                  $('#query').scrollTop(0);
                  $('#query').scrollLeft(hor_scroll_pos);
                });
							});
            }
          </script>
          ";
    }
    //*********************************************************************************************
		return $txt;
  }


  public function get_where_string()
  {
    if($this->default_where!='') { $w_str = $this->default_where; } else { $w_str = ""; }

    if(isset($_GET['filter']) && $_GET['filter']!='') { $f_werte = explode(';',$_GET['filter']); }
		else
		{
	    if(isset($_SESSION['curr_filters']) && $_SESSION['curr_filters']!='') { $f_werte = explode(';',$_SESSION['curr_filters']); } else { $f_werte = null; }
		}
    $i=0;
    foreach($this->columns as $col)
    {
			if($col->show_on_list)
			{
	      $name = $col->get_filter_column();
				if($f_werte !== null)
				{
		      if(count($f_werte)>$i)
		      {
		        if(trim($f_werte[$i])!='')
		        {
		          if($w_str!='') { $w_str.= " AND "; } //If is not the first where paramter, add AND to the string
	
	            $val = str_replace("<","",$f_werte[$i]);
	            $val = str_replace(">","",$val);
	            $val = str_replace("(NOT)","",$val);
	            $val = str_replace("(LEER)","NULL",$val);
	            $val = str_replace("(NULL)","NULL",$val);
	            $val = str_replace("*","%",$val);
	
		          if(($col->get_edit_typ()=='date' OR $col->get_edit_typ()=='datetime') AND $val != 'NULL')
		          {
		            $val = $this->date2iso(str_replace('=','',$val));
		          }
	
		          if(substr_count($val," OR ")>0)
							{
		            $w_str.= "(";
								$arr_vals = explode(' OR ',$val);
								foreach($arr_vals as $tmp)
								{
		              if(substr_count($tmp,"%")==0 && substr_count($tmp,"_")==0) { $w_str.= $name." = '".$tmp."' OR "; } else { $w_str.= $name." LIKE '".$tmp."' OR "; }
								}
								$w_str = substr($w_str,0,-4).")";
							}
							else
							{
			          if (substr($f_werte[$i],0,5)=='(NOT)') { if(substr_count($val,"%")==0 && substr_count($val,"_")==0) {  $w_str.= $name." <> '".$val."'"; } else { $w_str.= $name." NOT LIKE '".$val."'"; } }
			          elseif (substr($f_werte[$i],0,1)=='<') { $w_str.= $name." < '".$val."'"; }
			          elseif (substr($f_werte[$i],0,1)=='>') { $w_str.= $name." > '".$val."'"; }
			          elseif (substr($f_werte[$i],0,6)=='(LEER)') { $w_str.= $name." IS ".$val; }
			          elseif (substr($f_werte[$i],0,6)=='(NULL)') { $w_str.= $name." IS ".$val; }
			          else
								{
				          if($col->get_edit_typ()=='date') { $name = 'CONVERT(Char(10),'.$name.',120)'; }
				          if($col->get_edit_typ()=='datetime') { $name = 'CONVERT(Char(16),'.$name.',120)'; }
	
									if(substr($val,0,1)=="=")
									{
										$w_str.= $name." = '".substr($val,1)."'";
									}
									else
									{
										if(substr_count($val,"%")==0 && substr_count($val,"_")==0) { $w_str.= $name." LIKE '%".$val."%'"; } else { $w_str.= $name." LIKE '".$val."'";  }
									}
								}
							}
	
		        }
		      }
				}
	      $i++;
			}
    }
    if($w_str!='')
		{
			$test_str = substr($this->get_sql_select(),strpos($this->get_sql_select(),'FROM '.$this->get_sql_table())); //GROUP BY in the select part is not relevant for HAVING or WHERE
			if(strpos(strtoupper($test_str),"GROUP BY")===FALSE) { $ret_val = "WHERE ".$w_str; } else { $ret_val = "HAVING ".$w_str; }
// 			if(strpos(strtoupper($this->get_sql_select()),"SELECT * FROM (SELECT")===TRUE) { $ret_val = "WHERE ".$w_str; }
			if($this->save_filters_in_session) { $_SESSION['curr_where_string'] = $ret_val; if(isset($_GET['filter'])) { $_SESSION['curr_filters'] = $_GET['filter']; } }
      return $ret_val;
		}
		else
		{ return ""; }
  }

  public function get_sql_for_list()
  {
      //Get default sorting
    if(isset($this->default_order_by)) { $orderBy = " ORDER BY ".$this->default_order_by; } else { $orderBy=''; }
    if(isset($this->default_sort_dir)) { $sortDir = $this->default_sort_dir; } else { $sortDir=''; }

    //Get current sorting
    if(isset($_GET['orderBy']) && $_GET['orderBy']!='') { $orderBy = " ORDER BY ".$_GET['orderBy']; }
    if(isset($_GET['sortDir']) && $_GET['sortDir']!='') { $sortDir = $_GET['sortDir']; }

		//Check amount of data and add limitation if necessary
// 		$sql_str = $this->get_sql_select()." ".$this->get_where_string().$orderBy." ".$sortDir;
// 		$entries = $this->db->sql_query_with_fetch();

		if(substr_count($orderBy,',')>0) { $orderBy = str_replace(',',' '.$sortDir.',',$orderBy); $sortDir=''; }
    return str_replace('  ',' ',$this->get_sql_select()." ".$this->get_where_string().$orderBy." ".$sortDir);
  }

  public function set_default_order_by($val)
  {
    $this->default_order_by = $val;
  }
  public function get_default_order_by()
  {
    return $this->default_order_by;
  }

  public function set_default_sort_dir($val)
  {
    $this->default_sort_dir = $val;
  }
  public function get_default_sort_dir()
  {
    return $this->default_sort_dir;
  }

  public function set_sql_table($val)
  {
    $x = $this->db->sql_query("SHOW KEYS FROM ".$val." WHERE Key_name = 'PRIMARY'");
    if($this->db->count()==1)
    {
      $this->sql_table = $val;
      $d = $x->fetchObject();
      $this->set_id_column($d->Column_name);
    }
    else
    {
      throw new exception("<span style='font-size:16pt;'>SQL Table not found.</span> <p/><i>Check function <b>\"set_sql_table\"</b> from class <b>\"class_query\"</b> to define it.");
    }
  }
  public function get_sql_table()
  {
    return $this->sql_table;
  }

  public function set_id_column($val)
  {
    $this->id_column = $val;
  }
  public function get_id_column()
  {
    return $this->id_column;
  }

  public function set_default_where($val)
  {
    $this->default_where = $val;
  }
  public function get_default_where()
  {
    return $this->default_where;
  }

  public function set_reload($boolv)
  {
    $this->reload = $boolv;
  }

  public function get_reload()
  {
    if(isset($this->reload))
    {
      return $this->reload;
    }
    else
    {
      return false;
    }
  }
}
?>