<?php
/***************************************
 Han-Kwang Nienhuys' PHP captcha
 Copyright June 2007

 This copyright message and attribution must be preserved upon
 modification. Redistribution under other licenses is expressly allowed.
 Other licenses include GPL 2 or higher, BSD, and non-free licenses.
 The original, unrestricted version can be obtained from
 http://www.lagom.nl/linux/hkcaptcha/ .
***************************************/

class HKCaptcha 
{
	var $_fontPath;
	var $_perturbation;			// bigger numbers give more distortion; 1 is standard
	var $_imgWidth;				// image width, pixels
	var $_imgHeight;			// image height, pixels
	var $_circles;				// number of wobbly circles
	var $_lines;				// number of lines
	var $_bgcolor;
	var $_textcolor;
	var $_length;				// text lenght
	var $_captcha_string;		// captcha string

	function __construct()
	{
		$this->_fontPath 		= dirname(__FILE__).'/fonts/VeraSeBd.ttf';
		$this->_perturbation	= 1.2;
		$this->_imgWidth 		= 200;
		$this->_imgHeight 		= 95;
		$this->_circles 		= 0;
		$this->_lines 			= 2;
		$this->_length 			= 5;
		
		$this->_bgcolor			= '#F4F4F4';
		$this->_textcolor		= '#444444';
		
		// generate our initial captcha string
		$this->generateCaptchaString();
	}
	
	function setFontPath($path) 
	{
		$this->_fontPath = $path;
	}
	function setImageWidth($width) 
	{
		$width	= $width < 15 ? 15 : $width;
		$this->_imgWidth = $width;
	}
	function setImageHeight($height) 
	{
		$height	= $height < 15 ? 15 : $height;
		$this->_imgHeight = $height;
	}
	function setPertubation($perturbation) 
	{
		$this->_perturbation = $perturbation;
	}
	function setLength($length, $auto_generate=false) 
	{
		$this->_length	= $length < 1 ? 5 : $length;
		if ($auto_generate) {
			$this->generateCaptchaString();
		}
	}
	function setTextColor($color) 
	{
		$this->_textcolor = $color;
	}
	function setBgColor($color) 
	{
		$this->_bgcolor = $color;
	}
	
	function generateCaptchaString($type='alphanumeric')
	{
		$obj = new stdclass();
		// some easy-to-confuse letters taken out C/G I/l Q/O h/b 
		$obj->alpha			= 'ABDEFHKLMNOPRSTUVWXZabdefghikmnopqrstuvwxyz';
		$obj->numeric		= '0123456789';
		$obj->alphanumeric	= $obj->alpha.$obj->numeric;
		
		$letters = $obj->$type;
		$this->_captcha_string = '';
		for ($i = 0; $i < $this->_length; ++$i) {
			$this->_captcha_string .= substr($letters, rand(0,strlen($letters)-1), 1);
		}
		
		return $this->_captcha_string;
	}
	
	function getCaptchaString($type='alphanumeric')
	{
		if( empty($this->_captcha_string) ) {
			if( ($type === null) || empty($type) ) {
				$type	= 'alphanumeric';
			}
		
			$this->generateCaptchaString($type);
		}
		
		return $this->_captcha_string;
	}
	
	function rgb2Array($color)
	{
		return sscanf($color, '#%2x%2x%2x');
	}

	function frand() 
	{
		return 0.0001*rand(0,9999);
	}

	// wiggly random line centered at specified coordinates
	function randomline($img, $col, $x, $y)
	{
		$theta	= ($this->frand()-0.5)*M_PI*0.7;
		$len	= rand($this->_imgWidth*0.4, $this->_imgWidth*0.7);
		$lwid	= rand(0,2);

		$k		= $this->frand()*0.6+0.2; $k = $k*$k*0.5;
		$phi	= $this->frand()*6.28;
		$step	= 0.5;
		
		$dx		= $step*cos($theta);
		$dy		= $step*sin($theta);
		$n		= $len/$step;
		$amp	= 1.5*$this->frand()/($k+5.0/$len);
		$x0		= $x - 0.5*$len*cos($theta);
		$y0		= $y - 0.5*$len*sin($theta);

		$ldx	= round(-$dy*$lwid);
		$ldy	= round($dx*$lwid);
		for ($i=0; $i < $n; ++$i) {
			$x = $x0+$i*$dx + $amp*$dy*sin($k*$i*$step+$phi);
			$y = $y0+$i*$dy - $amp*$dx*sin($k*$i*$step+$phi);
			
			imagefilledrectangle($img, $x, $y, $x+$lwid, $y+$lwid, $col);
		}
	}

	// amp = amplitude (<1), num=numwobb (<1)
	function imagewobblecircle($img, $xc, $yc, $r, $wid, $amp, $num, $col)
	{
		$dphi = 1;
		if ($r > 0) {
			$dphi = 1/(6.28*$r);
		}
		$woffs = rand(0,100)*0.06283;
		for ($phi=0; $phi < 6.3; $phi += $dphi) {
			$r1 = $r * (1-$amp*(0.5+0.5*sin($phi*$num+$woffs)));
			$x = $xc + $r1*cos($phi);
			$y = $yc + $r1*sin($phi);
			
			imagefilledrectangle($img, $x, $y, $x+$wid, $y+$wid, $col);
		}
	}

	// make a distorted copy from $tmpimg to $img. $wid,$height apply to $img,
	// $tmpimg is a factor $iscale bigger.
	function distorted_copy($tmpimg, $img, $width, $height, $iscale)
	{
		$numpoles = 3;

		// make an array of poles AKA attractor points
		for ($i = 0; $i < $numpoles; ++$i) {
			do {
				$px[$i] = rand(0, $width);
			} while ($px[$i] >= $width*0.3 && $px[$i] <= $width*0.7);
			
			do {
				$py[$i] = rand(0, $height);
			} while ($py[$i] >= $height*0.3 && $py[$i] <= $height*0.7);
			
			$rad[$i]	= rand($width*0.4, $width*0.8);
			$tmp		= -$this->frand()*0.15-0.15;
			$amp[$i]	= $this->_perturbation * $tmp;
		}

		// get img properties bgcolor
		$bgcol = imagecolorat($tmpimg, 1, 1);
		$width2 = $iscale*$width;
		$height2 = $iscale*$height;

		// loop over $img pixels, take pixels from $tmpimg with distortion field
		for ($ix=0; $ix < $width; ++$ix) {
			for ($iy=0; $iy < $height; ++$iy) {
				$x = $ix;
				$y = $iy;
				
				for ($i = 0; $i < $numpoles; ++$i) {
					$dx = $ix - $px[$i];
					$dy = $iy - $py[$i];
					
					if ($dx == 0 && $dy == 0) continue;
					
					$r = sqrt($dx*$dx + $dy*$dy);
					if ($r > $rad[$i]) continue;
					
					$rscale = $amp[$i] * sin(3.14*$r/$rad[$i]);
					$x += $dx*$rscale;
					$y += $dy*$rscale;
				}
				
				$c = $bgcol;
				$x *= $iscale;
				$y *= $iscale;
				if ($x >= 0 && $x < $width2 && $y >= 0 && $y < $height2) {
					$c = imagecolorat($tmpimg, $x, $y);
				}
			
				imagesetpixel($img, $ix, $iy, $c);
			}
		}
	}

	// add grid for debugging purposes
	function addgrid($tmpimg, $width2, $height2, $iscale, $color) 
	{
		$lwid = floor($iscale*3/2);
		imagesetthickness($tmpimg, $lwid);
		
		for ($x=4; $x < $width2-$lwid; $x+=$lwid*2) {
			imageline($tmpimg, $x, 0, $x, $height2-1, $color);
		}
		for ($y = 4; $y < $height2-$lwid; $y+=$lwid*2) {
			imageline($tmpimg, 0, $y, $width2-1, $y, $color);
		}
	}

	function warped_text_image($width, $height, $string)
	{
		// internal scale factor for antialias
		$iscale		= 3;

		// initialize temporary image
		$width2		= $iscale * $width;
		$height2	= $iscale * $height;
		$tmpimg 	= imagecreate($width2, $height2);
		
		$color_bg	= $this->rgb2Array($this->_bgcolor);
		$bgColor 	= imagecolorallocatealpha(
			$tmpimg, 
			$color_bg[0], $color_bg[1], $color_bg[2], 
			10
		);
		
		$color_txt	= $this->rgb2Array($this->_textcolor);
		$col 		= imagecolorallocate($tmpimg, $color_txt[0], $color_txt[1], $color_txt[2]);

		// init final image
		$img 		= imagecreate($width, $height);
		imagepalettecopy($img, $tmpimg);    
		imagecopy($img, $tmpimg, 0,0 ,0,0, $width, $height);
		
		// put straight text into $tmpimage
		$fsize 		= $height2 * 0.25;
		$bb 		= imageftbbox($fsize, 0, $this->_fontPath, $string);
		$tx 		= $bb[4] - $bb[0];
		$ty 		= $bb[5] - $bb[1];
		$x	 		= floor($width2/2 - $tx/2 - $bb[0]);
		$y 			= round($height2/2 - $ty/2 - $bb[1]);
		imagettftext($tmpimg, $fsize, 0, $x, $y, -$col, $this->_fontPath, $string);

		// $this->addgrid($tmpimg, $width2, $height2, $iscale, $col); // debug

		// warp text from $tmpimg into $img
		$this->distorted_copy($tmpimg, $img, $width, $height, $iscale);

		// add wobbly circles (spaced)
		for ($i = 0; $i < $this->_circles; ++$i) {
			$x 		= $width * (1 + $i) / ($this->_circles + 1);
			$x 		+= (0.5-$this->frand()) * $width/$this->_circles;
			$y 		= rand($height * 0.1, $height * 0.9);
			$r 		= $this->frand();
			$r 		= ($r * $r + 0.2) + $height * 0.2;
			$lwid	= rand(0, 2);
			$wobnum	= rand(1, 4);
			$wobamp	= $this->frand() * $height * 0.01 / ($wobnum + 1);
			$this->imagewobblecircle($img, $x, $y, $r, $lwid, $wobamp, $wobnum, $col);
		}

		// add wiggly lines
		for ($i = 0; $i < $this->_lines; ++$i) {
			$x	= $width * (1+$i) / ($this->_lines + 1);
			$x	+= (0.5 - $this->frand()) * $width/$this->_lines;
			$y	= rand($height * 0.1, $height * 0.9);
			$this->randomline($img, $col, $x, $y);
		}

		return $img;
	}
	
	// start main program
	function render( $captcha_string='' ) 
	{
		if( empty($captcha_string) ) {
			$captcha_string = $this->_captcha_string;
		}
		
		$image = $this->warped_text_image($this->_imgWidth, $this->_imgHeight, $captcha_string);
		
		// add noise filter
		CaptchaFilters::addNoise($image, 5);
		
		// send several headers to make sure the image is not cached     
//		header('Content-type: image/png'); 

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");  
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  
		header("Cache-Control: no-store, no-cache, must-revalidate");  
		header("Cache-Control: post-check=0, pre-check=0", false);  
		header("Pragma: no-cache");      
		header('Content-type: image/png'); 

		// send the image to the browser 
		imagepng($image); 

		// destroy the image to free up the memory 
		imagedestroy($image);
	}
}

class CaptchaFilters 
{
	static function addNoise(&$image, $runs=30)
	{
		$width	= imagesx($image);
		$height	= imagesy($image);

		for($n=0; $n < $runs; $n++) {
			for($m=1; $m <= $height; $m++) {
				$randcolor = imagecolorallocate($image,
					mt_rand(0, 255),
					mt_rand(0, 255),
					mt_rand(0, 255)
				);

				imagesetpixel($image, mt_rand(1, $width), mt_rand(1, $height), $randcolor);
			}
		}
	}
	
	static function addSigns(&$image, $font, $cells=3) 
	{
		$width	= imagesx($image);
		$height	= imagesy($image);

		for ($i=0; $i < $cells; $i++) {
			$centerX     = mt_rand(5, $width);
			$centerY     = mt_rand(1, $height);
			$amount      = mt_rand(5, 10);
			$stringcolor = imagecolorallocatealpha($image, 128, 128, 128, 40);

			for ($n = 0; $n < $amount; $n++) {
				$signs = range('A', 'Z');
				$sign  = $signs[mt_rand(0, count($signs) - 1)];

				imagettftext($image, 15,
					mt_rand(-15, 15),
					$n * 15,
					30 + mt_rand(-5, 5),
					$stringcolor, $font, $sign
				);
			}
		}
	}
}