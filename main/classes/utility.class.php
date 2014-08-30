<?php
/*
* $Id: utility.class.php
* Helper class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class Utility
{
	/**
	Generate Base36/CRC32 hash code
		@param $str string
		@param $hash_type string
		@public
	*/
	public static function getHash( $str, $hash_type='crc32' ) 
	{
		$hash	= str_pad(
			base_convert(sprintf('%u', $hash_type($str)), 10, 36), 7, 'x', STR_PAD_LEFT
		);
		
		return $hash;
	}
	
	/**
	Create a token
		@param $length int
		@public
	**/
	function createToken( $length=5 )
	{
		static $chars	=	'0123456789abcdef';
		$max			=	strlen( $chars ) - 1;
		$token			=	'';
		$name 			=  session_name();
		for( $i = 0; $i < $length; ++$i) {
			$token .=	$chars[ (rand( 0, $max )) ];
		}

		return md5($token.$name);
	}
	
	/**
	Get $_REQUEST variables
		@param $key string
		@param $value string
		@public
	**/
	function getRequestParam($key, $value='')
	{
		$args	= explode('.', $key);

		if( count($args) > 1) {
			@session_start();
			
			$key		= $args{0};
			$request	= $args{1};
			
			$cur_value	= '';
			if( isset($_SESSION[$key][$request])) {
				$cur_value	= $_SESSION[$key][$request];
			}
			
			$vars		= Utility::getParams(null, false);
			if( isset($vars[$request])) {
				$cur_value					= $vars[$request];
				$_SESSION[$key][$request]	= $cur_value;
			}
			
			return ($cur_value ? $cur_value : $value);
		}
		
		return null;
	}
	
	/**
	Clear $_REQUEST values
		@param $request string
		@param $strict boolean
		@public
	**/
	function clearRequestParams( $request, $strict=true )
	{
		$requests	= explode('|', $request);

		if( count($requests) < 1) {
			return false;
		}
		
		@session_start();
		
		// create key=value mapping
		$keys		= array();
		$key_values	= array();
		if( $_SESSION) {
			foreach($_SESSION as $k=>$v) {
				if( is_array($v)) {
					foreach($v as $kk=>$vv) {
						$keys[]				= $k .'.'. $kk;
						$key_values[$kk]	= $k;
					}
				}
				else {
					$keys[]			= $k .'.'. $v;
					$key_values[$k]	= $k;
				}
			}
		}
		
		foreach($requests as $request) {
			@list($k, $r)	= explode('.', $request);
			
			if( !$strict) {
				if( in_array($r, array_keys($key_values))) {
					$k	= $key_values[$r];
				}
			}
			
			$_SESSION[$k][$r]	= null;
			unset($_SESSION[$k][$r]);
		}
		
		return true;
	}
	
	/**
	Get a specific request method variables
		@param $type string
		@param $parameterized boolean
		@param $exclude array
		@public
	**/
	function getParams( $type=null, $parameterized=true, $exclude=array() )
	{
		$types	= array();
		
		if( $type === null) {
			//$types[]	= 'REQUEST';
			$types[]	= 'GET';
			$types[]	= 'POST';
		} else {
			if( is_array($type)) {
				foreach($type as $_t) array_push($types, $_t);
			}
			else if( $type=='all') {
				$types[]	= 'REQUEST';
				$types[]	= 'SERVER';
				$types[]	= 'SESSION';
			}
			else array_push($types, $type);
		}
		
		// exclude
		$exclude[]	= 'layout';
		$exclude[]	= 'view';
		
		$values	= array();
		foreach($types as $_t) {
			$data	= null;
			$_t		= strtoupper($_t);
			switch($_t) {
				case 'COOKIE':
					$data	= $_COOKIE;
					break;
				case 'GET':
					$data	= $_GET;
					break;
				case 'POST':
					$data	= $_POST;
					break;
				case 'REQUEST':
					$data	= $_REQUEST;
					break;
				case 'SERVER':
					$data	= $_SESSION;
					break;
				case 'SESSION':
					@session_start();
					$data	= $_SERVER;
					break;
			}
		
			if( is_array($data)) {
				foreach($data as $k=>$v) {
					if( in_array($k, $exclude) ) continue;
					
					if( is_array($v)) {
						$_k				= $k;
						list($k, $v)	= each($v);
						$k				= $_k. '[' .$k . ']';
					}
					
					if( $parameterized )
						$values[]		= $k .'='. urlencode($v);
					else
						$values[$k]		= $v;
				}
			}
		}
		
		return $values;
	}
	
	/**
	Parse URI scheme from params
		@param $data string
		@public
	**/
	static function parseURISchemeData( $data )
	{
		if (empty($data)) {
			return false;
		}
		
		$result	= null;
		@list($data_type, $text)	= explode(';', $data);
		
		// get method
		if ($data_type) {
			@list($_s, $method)	= explode(':', $data_type);
		}
		// get source string
		if ($text) {
			// get delimiter pos
			if (($pos = strpos($text, ',')) !== false) {
				$encode_type	= substr($text, 0, $pos);
				$encoded_string	= substr($text, $pos, strlen($text));
				
				// decode
				switch ($encode_type) {
					case 'base64':
						$encoded_string	= base64_decode($encoded_string);
						break;
				}
				
				// apply method
				switch ($method) {
					case 'serialize':
						$result	= unserialize($encoded_string);
						break;
						
					case 'json':
						$result	= json_decode($encoded_string);
						break;
				}
			}
		}
		
		return $result;
	}
	
	/**
	Get client IP address
		@param $exclude_proxy boolean
		@public
	**/
	static function getClientIP( $exclude_proxy=false )
	{
		$proxy	= '';
		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			if (isset($_SERVER["HTTP_CLIENT_IP"])) {
				$proxy = $_SERVER["HTTP_CLIENT_IP"];
			} 
			else {
				$proxy = $_SERVER["REMOTE_ADDR"];
			}
			
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} 
		else {
			if (isset($_SERVER["HTTP_CLIENT_IP"])) {
				$ip = $_SERVER["HTTP_CLIENT_IP"];
			} 
			else {
				$ip = $_SERVER["REMOTE_ADDR"];
			}
		}
		
		if ($exclude_proxy) {
			$result	= $ip;
		}
		else {
			$result	= $ip.($proxy ? ' (proxy: '.$proxy.')' : '');
		}
		
		return $result;
	}
	
	/** 
	Set text limit
		@param $text string
		@param $limit int
		@param $allowd_tags string
		@public
	**/
	static function LimitText( $text, $limit, $allowed_tags='' ) 
	{
		$strip		= strip_tags($text);
		$endText	= (strlen($strip) > $limit) ? '...' : ''; 
		$strip		= substr($strip, 0, $limit);
		$striptag	= strip_tags($text, $allowed_tags);
		$lentag		= strlen($striptag);
		
		$display = "";
		
		$i = 0;
		$ignore = true;
		for($n = 0; $n < $limit; $n++) {
			for($m = $i; $m < $lentag; $m++) {
				$i++;
				if($striptag[$m] == "<") {
					$ignore = false;
				} else if($striptag[$m] == ">") {
					$ignore = true;
				}
				if($ignore == true) {
					if($strip[$n] != $striptag[$m]) {
						$display .= $striptag[$m];
					} else {
						$display .= $strip[$n];
						break;
					}
				} else {
					$display .= $striptag[$m];
				}
			}
		}
		
		return $display.$endText;
	}
	
	/**
	Set word limit
		@ref: K2 component > utilities.php
		@param $str string
		@param $limit int
		@param $end_char char
		@public
	**/
    function wordLimit( $str, $limit=100, $end_char='&#8230;') 
	{
		if (trim($str) == '') {
			return $str;
		}
		
		// always strip tags for text
		$str	= strip_tags($str);
		
		preg_match('/\s*(?:\S*\s*){'.(int) $limit.'}/', $str, $matches);
		if (strlen($matches[0]) == strlen($str)) {
			$end_char	= '';
		}
		
		return rtrim($matches[0]) . $end_char;
    }
    
    /**
	Clean and prepare text, remove any unwanted tags etc
		@param $text string
		@param $tidy_up boolean
		@param $convert_special_chars boolean
		@public
	**/
	static function CleanText( $text, $tidy_up=false, $convert_special_chars=true ) 
	{
		if (is_array($text)) {
			return;
		}
		
		// remove any javascript - OLLY
		// http://forum.joomla.org/index.php?topic=194800.msg1036857
		$regex	= "'<script[^>]*?>.*?</script>'si";
		$text	= preg_replace($regex, " ", $text);
		$regex	= "'<noscript[^>]*?>.*?</noscript>'si";
		$text	= preg_replace($regex, " ", $text);
   
		// convert html entities to chars
		// this doesnt remove &nbsp; but converts it to ascii 160
		// we handle that further down changing chr(160) to a space
		$text	= html_entity_decode($text);
		
		// strip any remaining html tags
		$text	= strip_tags($text);
		
		// remove any mambot codes
		$regex	= '(\{.*?\})';
		$text	= preg_replace($regex, " ", $text);
		
		// convert newlines and tabs to spaces
		if( $convert_special_chars) {
			$text	= str_replace(array("\r\n", "\r", "\n", "\t", chr(160)), " ", $text);
		}
		
		// remove any extra spaces
		while (strchr($text,"  ")) {
			$text = str_replace("  ", " ",$text);
		}
		
		if($tidy_up) {
			// general sentence tidyup
			for ($cnt = 1; $cnt < strlen($text); $cnt++) {
				// add a space after any full stops or comma's for readability
				// added as strip_tags was often leaving no spaces
				if (($text{$cnt} == '.') || ($text{$cnt} == ',')) {
					if (isset($text{$cnt+1}) && ($text{$cnt+1} != ' ')) {
						$text = substr_replace($text, ' ', $cnt + 1, 0);
					}
				}
			}
		}
		
		return trim($text);
	}
	
	/**
	Simple XSS fix
		@param $var string
		@param $convert_to_html boolean
		@public
	**/
	function cleanVar( $var, $convert_to_html=true )
	{
		// init db
		$db		=& Factory::getDBO();
		
		if( get_magic_quotes_gpc()) {
			$var	= stripslashes($var);
		}
		
		// utilize mysql string escape function
		$config	=& Factory::getConfig();
		if (strpos($config->dsn, 'mysql') !== false) {
			$var	= @mysql_real_escape_string($var);
		}
		
		if( $convert_to_html) {
			$var	= htmlentities($var);
		}
		
		return $var;
	}
	
	/**
	Password generator
		@ref: http://wiki.jumba.com.au/wiki/PHP_Generate_random_password
		@param $length int
		@public
	**/
	function generatePassword( $length ) 
	{
		$chars		= "234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		
		$i			= 0;
		$password 	= '';
		
		while ($i <= $length) {
			$password	.= $chars{mt_rand(0,strlen($chars))};
			$i++;
		}
		
		return $password;
	}
	
	/**
	Convert object to array
		@param $data array
		@public
	**/
	function toArray( $data )
	{
		$array	= array();
		
		if (is_object($data)) {
			foreach (get_object_vars($data) as $k=>$v) {
				$array[$k]	= $v;
			}
		}
		
		return $array;
	}
	
	/**
	String to url format
		@param $text string
		@param $create_link boolean
		@param $target string
		@param $link_title string
		@public
	**/
	function toURL( $text, $create_link=true, $target='_blank', $link_title='Follow link' )
	{
		if (!empty($text)) {
			$uri	= @parse_url($text);
			if ($uri === false) {
				return $text;
			}
			else {
				$uri	= array(
					(isset($uri['scheme']) ? $uri['scheme'] : 'http') . '://',
					@$uri['host'],
					@$uri['path'],
					@$uri['query']
				);

				// build link
				$uri	= implode('', $uri);
				
				if ($create_link) {
					$attributes	= array();
					if ($target) {
						$attributes[]	= "target=\"{$target}\"";
					}
					if ($link_title) {
						$attributes[]	= "title=\"{$link_title}\"";
					}
					
					$attributes	= implode(' ', $attributes);
					
					// return formatted url
					return "<a href=\"{$uri}\" {$attributes}>{$uri}</a>\n";
				}
				
				return $uri;
			}
		}
	}
	
	/**
	Create a unique name
		@param $text string
		@param $retain_orientation boolean
		@public
	**/
	public function unique_name( $text, $retain_orientation=false )
	{
		//mb_internal_encoding("utf-8");
		
		if (!$retain_orientation) {
			$text	= strtolower($text);
		}
		
		$text	= trim( trim(stripslashes(html_entity_decode($text))) );
		
		// clean-up 1st pass
		$text	= str_replace('&', 'and', $text);
		
		$text	= preg_replace('/[^a-zA-Z0-9_\- ]$/u', '', $text);
		$text	= preg_replace( '#\$([0-9]*)#', '\\\$${1}', $text);
		$text	= preg_replace('/\s+/', '-', $text );
		
		while(strpos($text, '--') !== false) {
			$text	= str_replace('--', '-', $text);
		}
		
		return $text;
	}
	
	/**
	Check if system function is disabled/enabled
		@param $method string
		@public
	**/
	function is_func_disabled( $method ) 
	{
		$items		= array();
		$functions	= explode(',', ini_get('disable_functions'));
		
		foreach ($functions as $func) {
			$items[] = trim($func);
		}
		
		return in_array($method, $items);
	}

	/**
	Parses file to key-value pairing ("parse_ini_file" function alias)
		@param $filename string
		@param $process_sections boolean
		@public
	**/
	function parse_ini_file( $filename, $process_sections=false ) 
	{
		if (Utility::is_func_disabled('parse_ini_file')) {
			// source: http://php.tonnikala.org/manual/ru/function.parse-ini-file.php
			// @modified 
			$ini	= file($filename);
			if (count($ini) == 0) {
				return array();
			}

			$sections	= array();
			$values		= array();
			$result		= array();
			$_globals	= array();
			
			$i	= 0;
			foreach ($ini as $line) {
				$line	= trim($line);
				$line	= str_replace("\t", " ", $line);

				// Comments
				if (!preg_match('/^[a-zA-Z0-9[_]/', $line)) {
					continue;
				}

				// Sections
				if ($line{0} == '[') {
					$tmp		= explode(']', $line);
					$sections[]	= trim(substr($tmp[0], 1));
					$i++;
					continue;
				}

				// Key-value pair
				list($key, $value)	= explode('=', $line, 2);
				$key	= trim($key);
				$value	= trim($value);
				
				if (strstr($value, ";")) {
					$tmp	= explode(';', $value);
					if (count($tmp) == 2) {
						if ((($value{0} != '"') && ($value{0} != "'")) ||
								preg_match('/^".*"\s*;/', $value) || preg_match('/^".*;[^"]*$/', $value) ||
								preg_match("/^'.*'\s*;/", $value) || preg_match("/^'.*;[^']*$/", $value) ){
							$value	= $tmp[0];
						}
					} 
					else {
						if ($value{0} == '"') {
							$value	= preg_replace('/^"(.*)".*/', '$1', $value);
						} 
						elseif ($value{0} == "'") {
							$value	= preg_replace("/^'(.*)'.*/", '$1', $value);
						} 
						else {
							$value	= $tmp[0];
						}
					}
				}
				
				$value	= trim($value);
				$value	= trim($value, "'\"");

				if ($i == 0) {
					if (strpos($line, '[]') !== false) {
						$key				= str_replace('[]', '', $key);
						$_globals[$key][]	= $value;
					} 
					else {
						$_globals[$key]		= $value;
					}
				} 
				else {
					if (strpos($line, '[]') !== false) {
						$key					= str_replace('[]', '', $key);
						$values[$i-1][$key][]	= $value;
					} 
					else {
						$values[$i-1][$key]		= $value;
					}
				}
			}

			if ($process_sections == true) {
				for ($j	= 0; $j < $i; $j++) {
					if ($process_sections == true) {
						$result[$sections[$j]]	= @$values[$j];
					} 
					else {
						$result	= @$values[$j];
					}
				}
			
				return $result + $_globals;
			}
			else {
				$value_arr	= array();
				foreach ($values as $i=>$items) {
					foreach ($items as $k=>$v) {
						$value_arr[$k]	= $v;
					}
				}
				
				return $value_arr;
			}
		}
		else {
			return parse_ini_file($filename, $process_sections);
		}
	}
	
	/** 
	Simple obfuscation function (encryption)
		@param $id int
		@param $salt string
		@public
	**/
	function encrypt( $id, $salt=null )
	{
		if ($salt) {
			$key	= md5($salt . $id);
		}
		else {
			$key	= md5($id);
		}
		
		// get random text based on locale time for suffix
		$r			= rand(0, 6);
		$suffix		= substr(time(), $r, 3);
		
		$key_1		= substr($key, 0, 3);
		$key_2		= substr($key, 3, strlen($key));
		
		// re-build key
		$key		= $key_1 .'-'. $key_2 . $suffix;
		
		return $key;
	}
	
	/** 
	Simple obfuscation function (decrypt)
		@param $key string
		@public
	**/
	function decrypt( $key )
	{
		$key	= substr($key, 0, strlen($key) - 3);
		$key	= strtolower($key);
		
		$key	= str_replace('%7C', '', $key);
		$key	= str_replace('|', '', $key);
		$key	= str_replace('-', '', $key);
		
		return $key;
	}
	
	/**
	Create a random text
		@param $length int
		@param $is_alphanum boolean
		@public
	**/
	static function randomText( $length=5, $is_alphanum=false )
	{
		// create random name
		$seed	= 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . ($is_alphanum ? '01234567890' : '');
		$text	= '';		
		
		// generate
		$n	= strlen($seed) - 1;
		for ($i=0; $i<$length; $i++) {
			$text	.= $seed[rand(0, $n)];
		}
		
		return $text;
	}

	/**
	String escape
		@param $text string
		@public
	*/
	static function escape( $text, $strip=false )
	{
		if (is_array($text)) {
			return array_map(__METHOD__, $text);
		}

		if (!empty($text) && is_string($text)) { 
			$text	= str_replace(array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), $text);
			
			if (in_array(strtolower(ini_get( 'magic_quotes_gpc')), array('1', 'on'))) {
				return $strip ? stripslashes($text) : addslashes($text);
			}
			else {
				return $strip ? stripslashes($text) : str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $text); 
			}
		} 

		return $text;
	} 	
	
	/**
	Rounds a float ("round" allias)
		@param $num int
		@param $precision int
		@param $mode int
		@param $decimal_separator char
		@public
	**/
	static function round( $num, $precision=0, $mode=1, $decimal_separator='.' )
	{
		// custom round function modes
		// 1 = strict (default): uses built-in round function
		// 2 = lateral string
		
		if ($mode === 2) {
			// zero
			if (empty($num)) {
				return $num;
			}
			
			// convert to string
			$num	= preg_replace('/[^0-9' .$decimal_separator. ']/', '', $num);
			@list($digits, $decimals)	= explode($decimal_separator, $num);
			
			$num	= $digits .$decimal_separator. ($precision ? substr($decimals, 0, $precision) : $decimals);
			
			return $num;
		}
		
		return round($num, $precision);
	}

	/**
	Returns size in bytes
		@param $val mixed
		@public
	*/
	static function toBytes( $val )
	{
		if (empty($val)) {
			return 0;
		}

		$val	= trim($val);
		preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);

		$suffix	= '';
		if (isset($matches[2])) {
			$suffix	= $matches[2];
		}

		if (isset($matches[1])) {
			$val	= (int)$matches[1];
		}

		switch (strtoupper($suffix)) {
			case 'G':
				$val	*= 1024;
			case 'M':
				$val	*= 1024;
			case 'K':
				$val	*= 1024;
		}

		return (int)$val;
	}
	
	/**
	Simple code minifier
		@param $text string
		@public
	**/
	static function minify( $text )
	{
		$text	= preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $text);
		$text	= str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $text);
		$text	= str_replace(': ', ':', $text);
		$text	= str_replace('{ ', '{', $text);
		$text	= str_replace(' }', '}', $text);
		$text	= str_replace(' {', '{', $text);
		$text	= str_replace('; ', ';', $text);
		
		return $text;
	}
}
