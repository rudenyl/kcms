<?php
/* 
 * $Id: validation.php
*/
 
class HelperClassValidation
{
	static function email( $email, $strict=false )
	{
		// check pattern
		if (preg_match('/^([\w\.\%\+\-]+)@([a-z0-9\.\-]+\.[a-z]{2,6})$/i', trim($email), $m)) {
			// strict
			if ($strict) {
				if ((checkdnsrr($m[2],'MX') == true) || (checkdnsrr($m[2],'A') == true)) {
					// host found!
					return true;
				}
				
				return false;
			}
			
			if( filter_var($email, FILTER_SANITIZE_EMAIL, FILTER_VALIDATE_EMAIL) ) {
				return true;
			}
		}
		
		return false;
	}
	
	static function numeric( $param )
	{
		return is_numeric($param);
	}
	
	static function alphanumeric( $param )
	{
		if(eregi('[^a-zA-Z0-9 ]', $param)) {
			return false;
		} else {
			return true;
		}
	}
	
	// obfuscate
	static function obfuscate( $str )
	{
		$link	= '';
		foreach(str_split($str) as $c) {
			$link	.= '&#' .ord($c). ';';
		}
		
		return $link;
	}
}
