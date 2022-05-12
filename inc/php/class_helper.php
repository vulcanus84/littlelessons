<?php
class helper
{

  function __construct()
  {
  }

	public function datediff($start_date,$end_date='',$intervall='d')
	{
		$d1 = new DateTime($start_date);
		$d2 = new DateTime($end_date);
		if($end_date=='')
		{
			$timezone = new DateTimeZone('Europe/Berlin');
			$d2->setTimezone($timezone);
			$d2 = new DateTime($d2->format('Y-m-d H:i:s'));
		}
		$diff = $d1->diff($d2,true);
						
	  switch($intervall)
		{
			case "y":
			   $total = $diff->y + $diff->m / 12 + $diff->d / 365.25; break;
			case "m":
			   $total= $diff->y * 12 + $diff->m + $diff->d/30 + $diff->h / 24;
			   break;
			case "d":
			   $total = $diff->y * 365.25 + $diff->m * 30 + $diff->d + $diff->h/24 + $diff->i / 60;
			   break;
			case "h":
			   $total = ($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h + $diff->i/60;
			   break;
			case "i":
			   $total = (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i + $diff->s/60;
			   break;
			case "s":
			   $total = ((($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i)*60 + $diff->s;
			   break;
		}
   	if( $diff->invert) { return -1 * $total; } else { return $total; }
	}

function s_datediff( $str_interval, $dt_menor, $dt_maior, $relative=false)
{
	$total = "";

       if( is_string( $dt_menor)) $dt_menor = date_create( $dt_menor);
       if( is_string( $dt_maior)) $dt_maior = date_create( $dt_maior);

       $diff = date_diff( $dt_menor, $dt_maior, ! $relative);

       switch( $str_interval){
           case "y":
               $total = $diff->y + $diff->m / 12 + $diff->d / 365.25; break;
           case "m":
               $total= $diff->y * 12 + $diff->m + $diff->d/30 + $diff->h / 24;
               break;
           case "d":
               $total = $diff->y * 365.25 + $diff->m * 30 + $diff->d + $diff->h/24 + $diff->i / 60;
               break;
           case "h":
               $total = ($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h + $diff->i/60;
               break;
           case "i":
               $total = (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i + $diff->s/60;
               break;
           case "s":
               $total = ((($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i)*60 + $diff->s;
               break;
          }
       if( $diff->invert)
               return -1 * $total;
       else    return $total;
   }

	public function date2iso($datestring)
	{
		if(gettype($datestring)=='object')
		{
			return $datestring->format('Y/m/d');
		}
		else
		{
			$datestring = trim($datestring);
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

			$t = "";
			if(substr_count($datestring,":")>0) { $t = substr($datestring,strpos($datestring," ")); }
			$datestring = sprintf("%04d-%02d-%02d", $year, $month, $day)." ".$t;
			return $datestring;
		}
	}
  
  public function get_browser_name()
  {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
    elseif (strpos($user_agent, 'Edge')) return 'Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Safari';
    elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
    
    return 'Other';
  }


}
?>