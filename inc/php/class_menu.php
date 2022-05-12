<?php
  class menu
  {
    private $items = array();
    public $menu_typ;
		public $check_permission=true;
    private $output_text='';
    private $last_link='';
    private $ul_close_allowed=false;
    private $entries;
		private $db;

    function __construct($db)
    {
      $this->menu_typ = 'simple';
			$this->db = $db;
    }

    function create_menu($id,$own_css=false)
    {
      $path = str_replace($_SERVER['DOCUMENT_ROOT'],"",$_SERVER['SCRIPT_FILENAME']);
      $path = substr($path,1);
			//$path = substr($path,strpos($path,"/"));
			//$path = substr($path,1);
      $own_folder = substr($path,0,strrpos($path,"/"));
      if(strpos($own_folder,"/")!=0) { $own_folder = substr($own_folder,strrpos($own_folder,"/")+1); }
      $dateiname = basename($_SERVER["REQUEST_URI"]);
      if(strpos($dateiname,".")==0) { $dateiname = "index.php"; }
      $output = "";

      switch($this->menu_typ)
      {
        case "simple":

					if(!$own_css)
					{
	          $output.= "
	          <style type='text/css'>
	            #menu { float:left;width:100%;background:#FFFFFF;font-size:93%;line-height:normal;border-bottom:1px solid #24618E;}
	            #menu ul {margin:0;padding:10px 10px 0 0px;list-style:none;}
	            #menu li {display:inline;margin:0;padding:0;}
	            #menu a {float:left;background:url('".level."inc/imgs/tableftJ.gif') no-repeat left top;margin:0;padding:0 0 0 5px;text-decoration:none;}
	            #menu a span {float:left;display:block;background:url('".level."inc/imgs/tabrightJ.gif') no-repeat right top;padding:5px 15px 4px 6px;color:#24618E;}
	            /* Commented Backslash Hack hides rule from IE5-Mac \*/
	            #menu a span {float:none;}
	            /* End IE5-Mac hack */

	            #menu a:hover span {color:#FFF;}
	            #menu a:hover {background-position:0% -42px;}
	            #menu a:hover span {background-position:100% -42px;}
	            #menu #current a {background-position:0% -42px;}
	            #menu #current a span {background-position:100% -42px;color:#FFF;}
	          </style>";
					}
          $output .= "        <div id='menu'>\n";
          $output .= "          <ul>\n";
          foreach($this->entries as $entry)
          {
            if(gettype($entry)=='object')
            {
              if($this->check_permission ===FALSE OR (isset($_SESSION['login_user']) && $_SESSION['login_user']->check_permission($own_folder."/".$entry->menu_link)))
              {
                if(strpos("/".$dateiname,"/".$entry->menu_link)!==FALSE)
                {
                  $output .= "            <li class='menu' id='current'><a href='$entry->menu_link'><span>$entry->menu_text</span></a></li>\n";
                }
                else
                {
                  $output .= "            <li class='menu'><a href='$entry->menu_link'><span>$entry->menu_text</span></a></li>\n";
                }
              }
            }
            else
            {
              if($entry=='level_down')
              {
                $output = substr($output,0,-6);
                $output .= "<ul>\n";
              }
              if($entry=='level_up')
              {
                $output .= "</li></ul>\n";
              }
            }
          }
          $output .= "          </ul>\n";
          $output .= "        </div>\n";
          break;
        case "extended":

          $output .= "        <div id='X'>\n";
          $output .= "          <ul id='menu'>\n";
					$anz_top_menu=0;
          foreach($this->entries as $entry)
          {
            if(gettype($entry)=='object')
            {
              if((isset($_SESSION['login_user']) && $_SESSION['login_user']->check_permission($own_folder."/".$entry->menu_link)) OR $entry->menu_link=='X')
              {
                if(strpos($dateiname,$entry->menu_link)!==FALSE)
                {
                  $output .= "            <li id='current'><a href='$entry->menu_link'><span>$entry->menu_text</span></a></li>\n";
                }
                else
                {
                  $output .= "            <li><a href='$entry->menu_link'><span>$entry->menu_text</span></a></li>\n";
                }
              }
            }
            else
            {
              if($entry=='level_down')
              {
								$anz_top_menu++;
                $output = substr($output,0,-6);
                $output .= "<ul>\n";
              }
              if($entry=='level_up')
              {
                $output .= "</ul></li>\n";
              }
            }
          }
          $output .= "          </ul>\n";
          $output .= "        </div>\n";
					$width_top_menu = 1100/$anz_top_menu-12;
					if(!$own_css)
					{
	          $output = "
	          <style type='text/css'>
	          	/* reset default styles */
	          	#menu,
	          	#menu ul { margin: 0; padding: 0; font-weight:lighter; }
	          	#menu li { list-style-type: none; }

	          	/* first level */
	          	#menu li,
	          	#menu a { float: left; margin-right:10px; width:".$width_top_menu."px; text-align:center; font-size:12pt;color:black; font-weight:bolder;padding-right:2px;}
	          	#menu a { display: block; background: #DDD; padding:5px; border-radius:10px;border:1px solid #FFF; }
	          	#menu a:hover,
	          	#menu a.menu_open { background: #DDD; text-decoration:none; }

	          	/* second level and up */
	          	#menu li ul { visibility: hidden; position: absolute; width: 200px; color:blue; font-weight:light;z-index:100;padding-top: 30px; }
	          	#menu li ul a { background: #DDD; font-weight:lighter;font-size:12pt;padding:5px;color:black; }
	          	#menu li ul a:hover,
	          	#menu li ul a.menu_open { background: #CCC; }

	          </style>".$output;
					}

					$output = "
          <script type='text/javascript'>
						$(document).ready(function() {
							$('#menu > li').bind('mouseover', openSubMenu);
							$('#menu > li').bind('mouseout', closeSubMenu);

							function openSubMenu() {
								$(this).find('ul').css('visibility', 'visible');
							};

							function closeSubMenu() {
								$(this).find('ul').css('visibility', 'hidden');
							};
						});
          </script>

					".$output;

          break;

      }
      return $output;

    }

    function add_item($text,$link,$with_translation=true)
    {
      if($text!='')
      {
        if($link=='') { $link='X'; }
        $this->entries[] = new menu_entry($text,$link,$this->db,$with_translation);
      }
    }

    function level_down()
    {
      $this->entries[] = "level_down";
      $this->menu_typ = "extended";
    }

    function level_up()
    {
      $this->entries[] = "level_up";
      $this->menu_typ = "extended";
    }

  }

  class menu_entry
  {
    public $menu_text;
    public $menu_link;

    function __construct($text,$link,$db,$with_translation)
    {
			if($with_translation)
			{
  	    if(isset($_SESSION['login_user']))
  			{
  				$this->t = new translation(clone($db),$_SESSION['login_user']->get_frontend_language());
  			}
  			else
  			{
  				$this->t = new translation($db);
  			}
	      $this->menu_text = $this->t->translate($text);
			}
			else
			{
	      $this->menu_text = $text;
			}
      $this->menu_link = $link;
    }

  }
?>