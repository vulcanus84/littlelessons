<?php
/**
 * EXAMPLE:
 * $myChart = new chart("circle","My circle chart", "Subtitle chart",900,300);
 * $myChart->add_row('Group1','10','#00FF00');
 * $myChart->add_row('Group2','20','#FF0000');
 * $myChart->add_row('Group3','30','#0000FF');
 * print "&lt;img src='data:image/jpeg;base64," . base64_encode($myChart->create()) . "'/>";
 * $myChart = null;
 */
 
class chart
{
	public $legend_width;
	private $chart_typ;
	private $title;
	private $subtitle;
	private $width;
	private $height;
	private $bg_color = '#FFFFFF';
	private $font_path;
	private $font_size_title = 14;
	private $font_size_subtitle = 12;
	private $font_size_right = 9;
	private $font_size_bottom;
	private $font_angle_bottom = 50;
	private $filename;
	private $data_max = 0;
	private $text_height = 0;
	private $arr_data;
	private $calc_max=true;
  private $legend_visible=true;

	public function __construct($chart_typ, $title,$subtitle, $width, $height, $font_size_bottom=14)
	{
		$this->title = $title;
		$this->subtitle = $subtitle;
		$this->width = $width;
		$this->height = $height;
		$this->chart_typ = $chart_typ;
		$this->font_size_bottom = $font_size_bottom;
    $this->font_path = level."inc/arial.ttf";
	}

  public function hide_legend()
  {
    $this->legend_visible=false;
  }

	public function add_row($description,$value=0,$color='')
	{
		if($color=='') { $r=rand(100,255);$g=rand(100,255);$b=rand(100,255); } else { $c = sscanf($color, '#%2x%2x%2x'); $r=$c[0];$g=$c[1];$b=$c[2];}
	 	if(is_array($value))
		{
	    $this->arr_data[] = array('description'=>$description,'value'=>$value,'color_r'=>$r,'color_g'=>$g,'color_b'=>$b);
      if($description!='XAXIS')
      {
  			foreach($value as $val)
  			{
  				if($this->data_max<$val) { $this->data_max = $val; }
  			}
      }
      else
      {
  			foreach($value as $val)
  			{
      		$box = imagettfbbox($this->font_size_bottom,$this->font_angle_bottom,$this->font_path,$val);
      		$height = abs($box[5] - $box[1]);
        	if($this->text_height<$height) { $this->text_height = $height; }
  			}
      }
		}
		else
		{
	    $this->arr_data[] = array('description'=>$description,'value'=>$value,'color_r'=>$r,'color_g'=>$g,'color_b'=>$b);
			if($this->data_max<$value) { $this->data_max = $value; }
  		if($this->chart_typ=='bars')
      {
        $box = imagettfbbox($this->font_size_bottom,$this->font_angle_bottom,$this->font_path,$description);
    		$height = abs($box[5] - $box[1]);
      }
      else
      {
        $box = imagettfbbox($this->font_size_bottom,0,$this->font_path,$description." (99.99%)");
    		$height = abs($box[4] - $box[0]);
      }
    	if($this->text_height<$height) { $this->text_height = $height; }
		}
	}

	public function set_max_value($val)
	{
		$this->data_max = $val; $this->calc_max = false;
	}

	public function create()
	{
    $f = 2; //Multiplication for better antialiasing
	  if($this->subtitle!= '') { $title_height = 80; } else { $title_height = 60; }

		//************************************************
		//Nice looking scales
		if($this->calc_max)
		{
			$anz_chars = strlen(round($this->data_max,0))-2;
			$val = $this->data_max;
			//if data is smaller than 0.5
			while(round($val,0)==0 AND $val!=0)
			{
				$val = $val*10;
				$anz_chars--;
			}
	    $this->data_max = (round($this->data_max/(5*pow(10,$anz_chars)),0)+1)*(5*pow(10,$anz_chars));
		}
		//************************************************


    switch($this->chart_typ)
    {
      case 'circle':

        $dia_width = $this->width-$this->text_height;
        $dia_height = $this->height-$title_height*1.5;

        $font_size = ($dia_width/20)*$f;
        if($font_size>30*$f) { $font_size = 30*$f; }
        if($font_size<10*$f) { $font_size = 10*$f; }

        $sx = $dia_width*$f*0.95;$sy=$dia_height*$f;$sz=$dia_width/20*$f;// Set Size-dimensions. SizeX,SizeY,SizeZ
        $cx = $dia_width/2*$f;$cy=($dia_height/2+$title_height)*$f; //Set Pie Postition. CenterX,CenterY

        $items = count($this->arr_data);
        $imgx=$this->width*$f;	$imgy=$this->height*$f;//Set Image Size. ImageX,ImageY

				$data_sum = 0;
        foreach($this->arr_data as $row)
        {
          $data_sum = $data_sum + $row['value'];
        }
        if($data_sum==0) { $data_sum=1; }

        //convert to angles.
        foreach($this->arr_data as $row)
        {
          $winkel = (($row['value'] / $data_sum) * 360);
          if ($winkel<1) { $winkel = 1; }
          $angle[] = $winkel;
          $angle_sum[] = array_sum($angle);
        }

        $im  = imagecreate($imgx,$imgy);
        imageantialias($im,true);
        $c = sscanf($this->bg_color, '#%2x%2x%2x');
        $background = imagecolorallocate($im, $c[0], $c[1], $c[2]);

        $schwarz=imagecolorallocate($im,0,0,0);

        foreach($this->arr_data as $row)
        {
          $colors[] = imagecolorallocate($im,$row['color_r'],$row['color_g'],$row['color_b']);
          $colord[] = imagecolorallocate($im,($row['color_r']/1.5),($row['color_g']/1.5),($row['color_b']/1.5));
        }

        //3D effect.
				$farbe = imagecolorallocate($im,100,100,100);
        for($z=1;$z<=$sz;$z++){
          // first slice
          imagefilledarc($im,$cx,($cy+$sz)-$z,$sx,$sy,0,$angle_sum[0],$farbe,IMG_ARC_EDGED);
          $i = 0;
          foreach($this->arr_data as $row)
          {
						if($i==0) { $angle_start = 0; } else { $angle_start = $angle_sum[$i-1]; }
            imagefilledarc($im,$cx,($cy+$sz)-$z,$sx,$sy,$angle_start,$angle_sum[$i],$colord[$i],IMG_ARC_NOFILL);
            $i++;
          }
        }

        //Top pie.
        imagefilledarc($im,$cx,$cy,$sx,$sy,0 ,$angle_sum[0], $colors[0], IMG_ARC_PIE);
        $i = 0;
        foreach($this->arr_data as $row)
        {
					if($i==0) { $angle_start = 0; } else { $angle_start = $angle_sum[$i-1]; }
          imagefilledarc($im,$cx,$cy,$sx,$sy,$angle_start,$angle_sum[$i], $colors[$i], IMG_ARC_PIE);
          $i++;
        }

        imagettftext($im,$this->font_size_title*$f,0,10,25*$f,$schwarz,$this->font_path,$this->title);
        imagettftext($im,$this->font_size_subtitle*$f,0,10,$this->font_angle_bottom*$f,$schwarz,$this->font_path,$this->subtitle);

        $pos_y = $dia_height*0.05;
        $pos_x = $dia_width*$f*1.05;

        if($this->legend_visible)
        {
          $j = 0;
          foreach($this->arr_data as $row)
          {
            $box_side_length = $dia_height/5*$f;
            imagefilledrectangle($im,$pos_x,$pos_y,$pos_x+$box_side_length,$pos_y+$box_side_length,$colors[$j]);
            $txt = $row['description']." (".round($row['value']/$data_sum*100,2)."%)";
            imagettftext($im,8*$f,0,$pos_x+$box_side_length+5*$f,$pos_y+$box_side_length/2,$schwarz,$this->font_path,$txt);
            $pos_y = $pos_y+$dia_height/4*$f;
            $j++;
          }
        }
				break;

      case 'bars':

        //Calculate free place for the diagramm
				if(count($this->arr_data)>$this->width/20)
				{
					$step_legend = ceil(count($this->arr_data)/($this->width/20));
				}
        $dia_height = $this->height-$title_height-$this->text_height/$f*1.1;
        if($this->font_size_right==0) { $this->font_size_right = ($this->width/100)*$f; } else { $this->font_size_right = $this->font_size_right * $f; }


        //Create Picture
        $imgx=$this->width*$f;
        $imgy=$this->height*$f;

        $im  = imagecreate($imgx,$imgy);
        imageantialias($im,true);

        //Set background
        $c = sscanf($this->bg_color, '#%2x%2x%2x');
        $background = imagecolorallocate($im, $c[0], $c[1], $c[2]);

        //Define color constants
        $schwarz=imagecolorallocate($im,0,0,0);
        $weiss=imagecolorallocate($im,255,255,255);

        imagettftext($im,$this->font_size_title*$f,0,10,25*$f,$schwarz,$this->font_path,$this->title);
        imagettftext($im,$this->font_size_subtitle*$f,0,10,$this->font_angle_bottom*$f,$schwarz,$this->font_path,$this->subtitle);

        //Define factor und width of the bars
        $items = count($this->arr_data);
        $f1 = $dia_height*$f / $this->data_max;
        $bar_width = $f*($this->width / (count($this->arr_data)*2.3));

        $bar_deep = round($bar_width/2,0);
        if($bar_deep>10*$f) { $bar_deep = 10*$f; }

        //Draw dashed line
        $style = array($schwarz,$schwarz,$schwarz,$schwarz,$schwarz, $weiss, $weiss, $weiss, $weiss, $weiss);
        imagesetstyle($im, $style);
        $y1 = $title_height*$f;
        $y2 = $y1 + $dia_height*$f;
        $step = ($y2-$y1)/5;
        for($i = 0;$i<6;$i++)
        {
          imageline($im,10,$y2-$i*$step-$bar_deep,$f*$this->width*0.9,$y2-$i*$step-$bar_deep,IMG_COLOR_STYLED);
          imagettftext($im,$this->font_size_right,0,$f*$this->width*0.92,$y2-$i*$step-$bar_deep,$schwarz,$this->font_path,$this->data_max/5*$i);
        }

        //Draw bars
        $i = 0;
        $legend_write = 1;
        foreach($this->arr_data as $row)
        {
          $color_front = 0;
          $color_top = $this->createcolor($im,$row['color_r'],$row['color_g'],$row['color_b']);
          $color_side = $this->createcolor($im,($row['color_r']/1.5),($row['color_g']/1.5),($row['color_b']/1.5));
          $color_front = $this->createcolor($im,($row['color_r']/1.2),($row['color_g']/1.2),($row['color_b']/1.2));
          $x1 = $i*$bar_width+30*$f;
          $x2 = ($i+1)*$bar_width+30*$f;
          $y1 = ($dia_height+$title_height)*$f-($f1*$row['value']);
          $j=0;
          while ($j<$bar_deep)
          {
            //imagefilledrectangle($im,$x1+$j,$y1-$j,$x2+$j,$y2-$j, $schwarz);
            imageline($im,$x1+$j,$y1-$j,$x2+$j,$y1-$j, $color_top);
            imageline($im,$x2+$j,$y1-$j,$x2+$j,$y2-$j, $color_side);
            $j++;
          }
          imagefilledrectangle($im,$x1,$y1,$x2,$y2, $color_front);
          $ty1 = $this->height*$f;
          $box = imageTTFBbox($this->font_size_bottom,$this->font_angle_bottom,$this->font_path,$row['description']);
					$text_width = abs($box[4] - $box[0])*$f;
					$text_height = abs($box[5] - $box[1])*$f;
					$y_offset = ($this->text_height*2)-$text_height;
          if(!isset($step_legend) OR $legend_write == $step_legend) { imagettftext($im,$this->font_size_bottom,$this->font_angle_bottom,$x1+($bar_width/2)-($text_width/2),$ty1-($y_offset/2),$schwarz,$this->font_path,$row['description']); $legend_write=1; } else { $legend_write++; }
          $i = $i+2;
        }
				break;

      case 'lines':

        //Search max. Text-length
        foreach($this->arr_data as $row)
				{
					$box = imagettfbbox($this->font_size_bottom,0,$this->font_path,$row['description']);
					$width = abs($box[0] - $box[2]);
			  	if($this->legend_visible) { if($this->legend_width<$width) { $this->legend_width = $width; } }
				}
				$this->legend_width = $this->legend_width + 50;

        $dia_width = $this->width-$this->legend_width;
        $dia_height = $this->height-$title_height*1.5;

        $font_size = ($dia_width/20)*$f;
        if($font_size>30*$f) { $font_size = 30*$f; }
        if($font_size<10*$f) { $font_size = 10*$f; }

        $items=0;
        foreach($this->arr_data as $row)
        {
          if($row['description']=='XAXIS')
          {
            foreach($row['value'] as $rowy)
            {
              $items++;
            }
          }
          break;
        }
        if($items==0) { $items=1; }

        $imgx=$this->width*$f;	$imgy=$this->height*$f;//Set Image Size. ImageX,ImageY

        //Calculate free place for the diagramm
				if(count($this->arr_data)>$this->width/20)
				{
					$step_legend = ceil(count($this->arr_data)/($this->width/20));
				}
        $dia_height = $this->height-$title_height-$this->text_height/$f-20;

        //Create Picture
        $imgx=$this->width*$f;
        $imgy=$this->height*$f;

        $im  = imagecreate($imgx,$imgy);
        imageantialias($im,true);

        //Set background
        $c = sscanf($this->bg_color, '#%2x%2x%2x');
        $background = imagecolorallocate($im, $c[0], $c[1], $c[2]);

        //Define color constants
        $schwarz=imagecolorallocate($im,0,0,0);
        $weiss=imagecolorallocate($im,255,255,255);

        imagettftext($im,$this->font_size_title*$f,0,10,25*$f,$schwarz,$this->font_path,$this->title);
        imagettftext($im,$this->font_size_subtitle*$f,0,10,$this->font_angle_bottom*$f,$schwarz,$this->font_path,$this->subtitle);

        //Define factor und width of the bars
        $f1 = $dia_height*$f / $this->data_max;
        $bar_width = $f*(($dia_width*0.9) / $items);

        //Draw dashed line
        $style = array($schwarz,$schwarz,$schwarz,$schwarz,$schwarz, $weiss, $weiss, $weiss, $weiss, $weiss);
        imagesetstyle($im, $style);
        $y1 = $title_height*$f;
        $y2 = $y1 + $dia_height*$f;
        $step = ($y2-$y1)/5;
        for($i = 0;$i<6;$i++)
        {
          imageline($im,30,$y2-$i*$step,$f*$dia_width,$y2-$i*$step,IMG_COLOR_STYLED);
          imagettftext($im,$this->font_size_bottom,0,$f*($dia_width+20),$y2-$i*$step,$schwarz,$this->font_path,$this->data_max/5*$i);
        }

        $pos_y = $dia_height/2;
        $pos_x = ($dia_width + 50) * $f;

        //Draw lines
        $i = 0;
				$j = 0;
        $legend_write = 1;
//         $this->font_size_bottom = 10;
        $text_width=0;
        foreach($this->arr_data as $row)
        {
          $color = imagecolorallocate($im,$row['color_r'],$row['color_g'],$row['color_b']);
          if($row['description']=='XAXIS')
          {
            //Calculate number of items, until Legend must be reduced becaus of the available space
            $arr_pos = imagettfbbox($this->font_size_bottom,180,$this->font_path,"HALLO");
            $legend_text_height = $arr_pos[5]-$arr_pos[1];
            $anz_legends = round($dia_width / $legend_text_height,0);
            $reduce_factor=1;
            while($items/$reduce_factor>$anz_legends)
            {
              $reduce_factor++;
            }
            foreach($row['value'] as $rowy)
            {
              $x1 = $i*$bar_width;
              $arr_pos = imagettfbbox($this->font_size_bottom,60,$this->font_path,$rowy);
              if($text_width==0) { $text_width = $arr_pos[4]-$arr_pos[0]+50; }
              $ty1 = $y2-$arr_pos[3]+25;
              $tx1 = $x1 + $text_width - $arr_pos[4]- ($this->font_size_bottom);
              if($i%$reduce_factor==0)
              {
                imagettftext($im,$this->font_size_bottom,60,$tx1,$ty1,$schwarz,$this->font_path,$rowy);
              }
              $i++;
            }
          }
          else
          {
            imagesetthickness($im, 3);
            $i=0;
            $lastx='';
            $lasty='';
            $x1='';
            $y1='';

            if($this->legend_visible)
            {
              imagefilledrectangle($im,$pos_x,$pos_y,$pos_x-$dia_height/20*$f,$pos_y-$dia_height/20*$f,$color);
              $txt = $row['description'];
              imagettftext($im,8*$f,0,$pos_x+10*$f,$pos_y-1*$f,$schwarz,$this->font_path,$txt);
            }
            $pos_y = $pos_y+$dia_height/10*$f;
            $j++;

            foreach($row['value'] as $rowy)
            {
              if($rowy!='')
              {
                $x1 = ($i*$bar_width)+$text_width;
                $y1 = ($dia_height+$title_height)*$f-($f1*$rowy);
              }
              else
              {
                $x1 = '';
                $y1 = '';
              }
              if($lastx!='' AND $x1!='')
              {
                imageline($im,$lastx,$lasty,$x1,$y1, $color);
              }
              $lastx = $x1;
              $lasty = $y1;
              $i++;
            }
          }
        }
				break;

    } //End switch chart_typ

    // Resample for antialiasing
    $image_p = imagecreatetruecolor($this->width, $this->height);
    imagecopyresampled($image_p, $im, 0, 0, 0, 0, $this->width, $this->height, $this->width*$f, $this->height*$f);

    //Output
    ob_start();
    imagejpeg($image_p, NULL, 100);
    $rawImageBytes = ob_get_clean();

    imagedestroy($im);
		return $rawImageBytes;
	}

	private	function createcolor($pic,$c1,$c2,$c3)
	{
	  //get color from palette
	  $color = imagecolorexact($pic, $c1, $c2, $c3);
	  if($color==-1)
		{
       //color does not exist...
       //test if we have used up palette
       if(imagecolorstotal($pic)>=255)
			 {
          //palette used up; pick closest assigned color
          $color = imagecolorclosest($pic, $c1, $c2, $c3);
       }
			 else
			 {
          //palette NOT used up; assign new color
          $color = imagecolorallocate($pic, $c1, $c2, $c3);
       }
	  }
	  return $color;
   }

}

?>
