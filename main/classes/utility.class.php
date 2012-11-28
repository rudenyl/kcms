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
		for( $i = 0; $i < $length; ++$i ) {
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

		if( count($args) > 1 ) {
			@session_start();
			
			$key		= $args{0};
			$request	= $args{1};
			
			$cur_value	= '';
			if( isset($_SESSION[$key][$request]) ) {
				$cur_value	= $_SESSION[$key][$request];
			}
			
			$vars		= Utility::getParams(null, false);
			if( isset($vars[$request]) ) {
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

		if( count($requests) < 1 ) {
			return false;
		}
		
		@session_start();
		
		// create key=value mapping
		$keys		= array();
		$key_values	= array();
		if( $_SESSION ) {
			foreach($_SESSION as $k=>$v) {
				if( is_array($v) ) {
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
			
			if( !$strict ) {
				if( in_array($r, array_keys($key_values)) ) {
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
		
		if( $type === null ) {
			//$types[]	= 'REQUEST';
			$types[]	= 'GET';
			$types[]	= 'POST';
		} else {
			if( is_array($type) ) {
				foreach($type as $_t) array_push($types, $_t);
			}
			else if( $type=='all' ) {
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
		
			if( is_array($data) ) {
				foreach($data as $k=>$v) {
					if( in_array($k, $exclude) ) continue;
					
					if( is_array($v) ) {
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
		if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ) {
			if ( isset($_SERVER["HTTP_CLIENT_IP"]) ) {
				$proxy = $_SERVER["HTTP_CLIENT_IP"];
			} 
			else {
				$proxy = $_SERVER["REMOTE_ADDR"];
			}
			
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} 
		else {
			if ( isset($_SERVER["HTTP_CLIENT_IP"]) ) {
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
		if ( trim($str) == '' ) {
			return $str;
		}
		
		// always strip tags for text
		$str	= strip_tags($str);
		
		preg_match('/\s*(?:\S*\s*){'.(int) $limit.'}/', $str, $matches);
		if ( strlen($matches[0]) == strlen($str) ) {
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
		if( $convert_special_chars ) {
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
					if ( isset($text{$cnt+1}) && ($text{$cnt+1} != ' ') ) {
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
		
		if( get_magic_quotes_gpc() ) {
			$var	= stripslashes($var);
		}
		
		// utilize mysql string escape function
		$config	=& Factory::getConfig();
		if (strpos($config->dsn, 'mysql') !== false) {
			$var	= @mysql_real_escape_string($var);
		}
		
		if( $convert_to_html ) {
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
			foreach( get_object_vars($data) as $k=>$v ) {
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
		
		$text	= preg_replace('/[^a-zA-Z0-9._\- ]/', '', $text);
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
			// source: http://forum.dokuwiki.org/post/6634
			$lines			= file($filename);
			$section		= '';
			
			for ($line_num=0; $line_num <= sizeof($lines); $line_num++) {
				$filedata	= $lines[$line_num];
				$dataline	= trim($filedata);
				$firstchar	= substr($dataline, 0, 1);
				
				if ($firstchar != ';' && $dataline != '') {
					//It's an entry (not a comment and not a blank line)
					if ($firstchar == '[' && substr($dataline, -1, 1) == ']' && $process_sections) {
						//It's a section
						$section	= substr($dataline, 1, -1);
					} 
					else {
						//It's a key...
						$delimiter	= strpos($dataline, '=');
						if ($delimiter > 0) {
							//...with a value
							$key					= trim(substr($dataline, 0, $delimiter));
							$data[$section][$key]	= '';
							$value					= trim(substr($dataline, $delimiter + 1));
							
							while (substr($value, -1, 1) == ';') {
								//...value continues on the next line
								$value					= substr($value, 0, strlen($value)-1);
								$data[$section][$key]	.= stripcslashes($value);
								$line_num++;
								
								$value					= trim($lines[$line_num]);
							}
							
							$data[$section][$key]		.= stripcslashes($value);
							$data[$section][$key]		= trim($data[$section][$key]);
							
							if (substr($data[$section][$key], 0, 1) == '"' && substr($data[$section][$key], -1, 1) == '"') {
								$data[$section][$key]	= substr($data[$section][$key], 1, -1);
							}
						}
						else {
							//...without a value
							$data[$section][trim($dataline)]	= '';
						}
					}
				} 
				else {
					//It's a comment or blank line.  Ignore.
				}
			}
			
			return $data;
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
		$key		= $key_1 .'|'. $key_2 . $suffix;
		
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
		
		$key	= str_replace('%7c', '', $key);
		$key	= str_replace('|', '', $key);
		
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
}
