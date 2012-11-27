<?php
/*
* $Id: image.class.php, version 0.1.1212011
*
* Application core class
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class Image
{
	/**
	Class constructor
		@public
	**/
	function __construct() 
	{
	}
	
	/**
	Create image thumbnail
		@param $file string
		@param $imgWidth integer
		@param $imgHeight integer
		@param $dest string
		@param $prefix string
		@public
	**/
	public function thumb( $file, $imgWidth, $imgHeight, $dest='', $prefix='', $crop=true) 
	{
		$funcPrefix	= 'image';
	
		// do nothing
		//if( !file_exists($file) || !is_file($file) ) return null;
	
		// get allowed extensions
		if ( !preg_match('/\.(gif|jp[e]*g|png|bmp)$/', $file, $ext) ) {
			// not supported
			return null;
		}
		$fileExt	= str_replace('jpg', 'jpeg', $ext[1]);
		
		if( $fileExt == 'bmp') {
			/*
			if (!function_exists('imagecreatefrombmp')) { 
				$funcPrefix	= 'self::image';
				$img		= self::imagecreatefrombmp( $file );
			}
			else {
				$img		= imagecreatefrombmp( $file );
			}
			*/
			
			// disable for now
			return null;
		}
		else {
			$img	= imagecreatefromstring( file_get_contents($file) );
		}
		
		// set compression quality
		$compression	= 95;
		switch( $fileExt ) {
			case 'png':
				$compression	= 9;
				break;
		}

		
		// Get image dimensions
		$_w			= imagesx($img);
		$_h			= imagesy($img);
		$offsetx	= 0;
		$offsety	= 0;
		
		// Get image aspect ratio
		if( $crop === true ) {
			if($_w < $imgWidth) {
				$imgWidth	= $_w;
			}
			
			if($_h < $imgHeight) {
				$imgHeight	= $_h;
			}
			
			$_rw	= $_w / $imgWidth;
			$_rh	= $_h / $imgHeight;
			if ($_rw < $_rh) {
				$ratio = $_rw; 
			}
			else {
				$ratio = $_rh;
			}
			
			// center
			$offsetx	= floor(($_w - ($imgWidth * $ratio)) * 0.5);
			$offsety	= floor(($_h - ($imgHeight * $ratio)) * 0.5);
			$_w			= (int)($imgWidth * $ratio);
			$_h			= (int)($imgHeight * $ratio);
		}
		else {
			$use_ratio	= true;
			
			// get crop info
			if( is_array($crop) ) {
				if( isset($crop['offsetx']) ) {
					$offsetx	= (int)$crop['offsetx'];
				}
				if( isset($crop['offsety']) ) {
					$offsety	= (int)$crop['offsety'];
				}
				if( isset($crop['width']) ) {
					$_w			= (int)$crop['width'];
				}
				if( isset($crop['height']) ) {
					$_h			= (int)$crop['height'];
				}
				
				if( isset($crop['no_ratio']) ) {
					$use_ratio	= false;
				}
			}
		
			if( $use_ratio ) {
				$ratio	= ($_w / $_h);
				if ($imgWidth <= $_w && ($imgWidth/$ratio) <= $imgHeight) {
					$imgHeight	= $imgWidth/$ratio;
				}
				elseif ($imgHeight <= $_h && ($imgHeight*$ratio) <= $imgWidth) {
					$imgWidth	= ($imgHeight * $ratio);
				}
				else {
					$imgWidth	= $_w;
					$imgHeight	= $_h;
				}
			}
		}
		
		// Create blank image
		$tmpImage	= imagecreatetruecolor($imgWidth, $imgHeight);
		$bg			= imagecolorallocate($tmpImage, 0xFF, 0xFF, 0xFF);
		imagefill($tmpImage, 0, 0, $bg);
		
		// Resize image
		imagecopyresampled($tmpImage, $img, 0, 0, $offsetx, $offsety, $imgWidth, $imgHeight, $_w, $_h);
		
		// Set transparent bg
		imagecolortransparent($tmpImage, $bg);
		
		// check if valid file dest, if not send to browser
		$destPath	= dirname($dest);
		if( !is_dir($destPath) ) {
			$dest	= null;
		
			// Set the content type header
			header('Content-type: image/' .$fileExt);
		}
		if( !empty($prefix) ) {
			// get parts
			$p	= pathinfo($file);
			
			// create destination file
			$dest	= $p['dirname'] .DS. $prefix . $p['basename'];
		}
		
		eval($funcPrefix.$fileExt.'($tmpImage, $dest, $compression);');
		
		// free up memory
		imagedestroy($tmpImage);
		
		return true;
	}
	
	/**
	 * Creates function imagecreatefrombmp, since PHP doesn't have one
	 * @return resource An image identifier, similar to imagecreatefrompng
	 * @param string $filename Path to the BMP image
	 * @see imagecreatefrompng
	 * @author Glen Solsberry <glens@networldalliance.com>
	 */
	function imagecreatefrombmp($filename) 
	{
		// version 1.00
		if (!($fh = fopen($filename, 'rb'))) {
			trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
			return false;
		}
		// read file header
		$meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));
		// check for bitmap
		if ($meta['type'] != 19778) {
			trigger_error('imagecreatefrombmp: ' . $filename . ' is not a bitmap!', E_USER_WARNING);
			return false;
		}
		// read image header
		$meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));
		// read additional 16bit header
		if ($meta['bits'] == 16) {
			$meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
		}
		// set bytes and padding
		$meta['bytes'] = $meta['bits'] / 8;
		$meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4)- floor($meta['width'] * $meta['bytes'] / 4)));
		if ($meta['decal'] == 4) {
			$meta['decal'] = 0;
		}
		// obtain imagesize
		if ($meta['imagesize'] < 1) {
			$meta['imagesize'] = $meta['filesize'] - $meta['offset'];
			// in rare cases filesize is equal to offset so we need to read physical size
			if ($meta['imagesize'] < 1) {
				$meta['imagesize'] = @filesize($filename) - $meta['offset'];
				if ($meta['imagesize'] < 1) {
					trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $filename . '!', E_USER_WARNING);
					return false;
				}
			}
		}
		// calculate colors
		$meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];
		// read color palette
		$palette = array();
		if ($meta['bits'] < 16) {
			$palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
			// in rare cases the color value is signed
			if ($palette[1] < 0) {
				foreach ($palette as $i => $color) {
					$palette[$i] = $color + 16777216;
				}
			}
		}
		// create gd image
		$im = imagecreatetruecolor($meta['width'], $meta['height']);
		$data = fread($fh, $meta['imagesize']);
		$p = 0;
		$vide = chr(0);
		$y = $meta['height'] - 1;
		$error = 'imagecreatefrombmp: ' . $filename . ' has not enough data!';
		// loop through the image data beginning with the lower left corner
		while ($y >= 0) {
			$x = 0;
			while ($x < $meta['width']) {
				switch ($meta['bits']) {
					case 32:
					case 24:
						if (!($part = substr($data, $p, 3))) {
							trigger_error($error, E_USER_WARNING);
							return $im;
						}
						$color = unpack('V', $part . $vide);
						break;
					case 16:
						if (!($part = substr($data, $p, 2))) {
							trigger_error($error, E_USER_WARNING);
							return $im;
						}
						$color = unpack('v', $part);
						$color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3);
						break;
					case 8:
						$color = unpack('n', $vide . substr($data, $p, 1));
						$color[1] = $palette[ $color[1] + 1 ];
						break;
					case 4:
						$color = unpack('n', $vide . substr($data, floor($p), 1));
						$color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
						$color[1] = $palette[ $color[1] + 1 ];
						break;
					case 1:
						$color = unpack('n', $vide . substr($data, floor($p), 1));
						switch (($p * 8) % 8) {
							case 0:
								$color[1] = $color[1] >> 7;
								break;
							case 1:
								$color[1] = ($color[1] & 0x40) >> 6;
								break;
							case 2:
								$color[1] = ($color[1] & 0x20) >> 5;
								break;
							case 3:
								$color[1] = ($color[1] & 0x10) >> 4;
								break;
							case 4:
								$color[1] = ($color[1] & 0x8) >> 3;
								break;
							case 5:
								$color[1] = ($color[1] & 0x4) >> 2;
								break;
							case 6:
								$color[1] = ($color[1] & 0x2) >> 1;
								break;
							case 7:
								$color[1] = ($color[1] & 0x1);
								break;
						}
						$color[1] = $palette[ $color[1] + 1 ];
						break;
					default:
						trigger_error('imagecreatefrombmp: ' . $filename . ' has ' . $meta['bits'] . ' bits and this is not supported!', E_USER_WARNING);
						return false;
				}
				imagesetpixel($im, $x, $y, $color[1]);
				$x++;
				$p += $meta['bytes'];
			}
			$y--;
			$p += $meta['decal'];
		}
		fclose($fh);
		
		return $im;
	}
	
	/*///////////////////////////////////////////////*/ 
	/*// BMP creation group //*/ 
	/*///////////////////////////////////////////////*/ 
	/* ImageBMP */ 
	// http://alufis35.uv.es/websvn/filedetails.php?repname=spip&path=/mollio/branches/1.9/plugins/article_pdf/pdf/GifSplit.class.php&sc=1
	function imagebmp($img, $file, $RLE=0) 
	{ 
		$ColorCount = imagecolorstotal($img); 
		$Transparent = imagecolortransparent($img); 
		$IsTransparent = $Transparent != -1; 
		
		if($IsTransparent) 
			$ColorCount--; 
			
		$retd		= 0;
		$BitCount 	= 1;
		
		if($ColorCount == 0) { 
			$ColorCount = 0; 
			$BitCount = 24; 
		} 
		if(($ColorCount > 0) && ($ColorCount <= 2)) {
			$ColorCount = 2; 
			$BitCount = 1; 
		} 
		if(($ColorCount > 2) && ($ColorCount <= 16)) { 
			$ColorCount = 16; 
			$BitCount = 4; 
		} 
		if(($ColorCount > 16) && ($ColorCount <= 256)) { 
			$ColorCount = 0; 
			$BitCount = 8; 
		} 
		$Width = imageSX($img); 
		$Height = imageSY($img); 
		$Zbytek = (4 - ($Width / (8 / $BitCount)) % 4) % 4; 
		if($BitCount < 24) 
		$palsize = pow(2, $BitCount) * 4; 
		$size = (floor($Width / (8 / $BitCount)) + $Zbytek) * $Height + 54; 
		$size += $palsize; 
		$offset = 54 + $palsize; 
		// Bitmap File Header 
		$ret = 'BM'; 
		$ret .= self::int_to_dword($size); 
		$ret .= self::int_to_dword(0); 
		$ret .= self::int_to_dword($offset); 
		// Bitmap Info Header 
		$ret .= self::int_to_dword(40); 
		$ret .= self::int_to_dword($Width); 
		$ret .= self::int_to_dword($Height); 
		$ret .= self::int_to_word(1); 
		$ret .= self::int_to_word($BitCount); 
		$ret .= self::int_to_dword($RLE); 
		$ret .= self::int_to_dword(0); 
		$ret .= self::int_to_dword(0); 
		$ret .= self::int_to_dword(0); 
		$ret .= self::int_to_dword(0); 
		$ret .= self::int_to_dword(0); 
		// image data 
		$CC = $ColorCount; 
		$sl1 = strlen($ret); 
		if($CC == 0) $CC = 256; 
		
		if($BitCount < 24) {
			$ColorTotal = imagecolorstotal($img); 
			if($IsTransparent) $ColorTotal--; 
			
			for($p = 0; $p < $ColorTotal; $p++) { 
				$color = imagecolorsforindex($img, $p); 
				$ret .= self::inttobyte($color["blue"]); 
				$ret .= self::inttobyte($color["green"]); 
				$ret .= self::inttobyte($color["red"]); 
				$ret .= self::inttobyte(0); 
			} 
			$CT = $ColorTotal; 
			for($p = $ColorTotal; $p < $CC; $p++) { 
				$ret .= self::inttobyte(0); 
				$ret .= self::inttobyte(0); 
				$ret .= self::inttobyte(0); 
				$ret .= self::inttobyte(0); 
			} 
		} 
		if($BitCount <= 8) { 
			for($y = $Height - 1; $y >= 0; $y--) { 
				$bWrite = ""; 
				for($x = 0; $x < $Width; $x++) { 
					$color = imagecolorat($img, $x, $y); 
					$bWrite .= self::decbinx($color, $BitCount); 
					
				if(strlen($bWrite) == 8) { 
					$retd .= self::inttobyte(bindec($bWrite)); 
					$bWrite = ""; 
				} 
			} 
			
			if((strlen($bWrite) < 8) and (strlen($bWrite) != 0)) { 
				$sl = strlen($bWrite); 
				
				for($t = 0; $t < 8 - $sl; $t++) $sl .= "0"; 
				$retd .= self::inttobyte(bindec($bWrite)); 
			} 
			
			for($z = 0; $z < $Zbytek; $z++) $retd .= self::inttobyte(0); 
			} 
		}
		
		if(($RLE == 1) and ($BitCount == 8)) { 
			for($t = 0; $t < strlen($retd); $t += 4) { 
				if($t != 0) 
					if(($t) % $Width == 0) $ret .= chr(0).chr(0); 
					
				if(($t + 5) % $Width == 0) { 
					$ret .= chr(0).chr(5).substr($retd, $t, 5).chr(0); 
					$t += 1; 
				} 
				if(($t + 6) % $Width == 0) { 
					$ret .= chr(0).chr(6).substr($retd, $t, 6); 
					$t += 2; 
				} 
				else $ret .= chr(0).chr(4).substr($retd, $t, 4); 
			} 
			
			$ret .= chr(0).chr(1); 
		} 
		else $ret .= $retd; 
		
		if($BitCount == 24) { 
			for($z = 0; $z < $Zbytek; $z++) $Dopl .= chr(0); 
			
			for($y = $Height - 1; $y >= 0; $y--) { 
				for($x = 0; $x < $Width; $x++) { 
					$color = imagecolorsforindex($img, ImageColorAt($img, $x, $y)); 
					$ret .= chr($color["blue"]).chr($color["green"]).chr($color["red"]); 
				} 
				$ret .= $Dopl; 
			} 
		} 
		
		if(fwrite(fopen($file, "wb"), $ret)) 
			return true; 
		else 
			return false; 
	} 
	function int_to_word($n)
	{
		return chr($n & 255).chr(($n >> 8) & 255);
	}
	function int_to_dword($n)
	{
		return chr($n & 255).chr(($n >> 8) & 255).chr(($n >> 16) & 255).chr(($n >> 24) & 255); 
	}
	function inttobyte($n)
	{
		return chr($n);
	}
	function decbinx($d,$n)
	{
		$bin = decbin($d);
		$sbin = strlen($bin);
		for($j = 0; $j < $n - $sbin; $j++) $bin = "0$bin";
		
		return $bin;
	}
	
}