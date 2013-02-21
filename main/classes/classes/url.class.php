<?php
/*
* $Id: url.class.php, version 0.1.030211
* URL manipulation base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class URL
{
	/**
	Class constructor
		@public
	**/
	function __construct()
	{
	}
	
	/**
	Routing implemention shorthand
		@param $url string
		@param $params string
		@param $force_ssl boolean
	**/
	static public function _( $url, $params=null, $force_ssl=false )
	{
		$__app	=& Factory::getApplication();
		$config	= $__app->get('config');
		
		$url	= str_replace('&amp;', '&', $url);
	
		if ($config->SEFURL) {
			// Get current language
			$lang_code	= I18N::getCurrentLanguage();
			
			// url prefix
			$lang_prefix	= '';
			
			// Add language
			if ($lang_code) {
				// add to route
				$lang_prefix	= $lang_code . '/';
			}
			
			// create param link
			$params	= empty($params) ? '' : ($params{0} != '#' ? '?' : '') . $params;
		
			$uri_vars	= parse_url($url);
			
			$query		= isset($uri_vars['query']) ? $uri_vars['query'] : '';
			$qparams	= explode('&', $query);
		
			$segment	= array();
			for($i=0, $n=count($qparams); $i<$n; $i++) {
				@list($k, $v)	= explode("=", $qparams[$i]);
				$segment[$k]	= $v;
			}
			
			// build
			if (isset($segment['view'])) {
				$route		= array();
				
				// save URL tag
				$save_url	= false;
				
				// get SEF implementation per app
				if (isset($segment['app'])) {
					$app		= $segment['app'];
				}
				else {
					$app		= Request::getVar('app', 'default');
				}
				$appSEFName		= 'App' . ucfirst($app) .'SEF';
				
				$appSEFPath	= PATH_APPLICATIONS .DS. $app .DS. 'sef.php';
				if (file_exists($appSEFPath) && is_file($appSEFPath)) {
					require_once( $appSEFPath );
					
					$appSEFName		= 'App' . ucfirst($app) .'SEF';
					$appSEFClass	= new $appSEFName();
					
					if (is_subclass_of($appSEFClass, 'SEF')) {
						// check if can save url links
						if (method_exists($appSEFClass, 'saveURL')) {
							$save_url	= $appSEFClass->saveURL();
						}
						
						$appSEFTask		= 'BuildSEFRoute';
						if (method_exists($appSEFClass, $appSEFTask)) {
							// execute task
							$route		= $appSEFClass->$appSEFTask( $segment, $save_url );
						}
					}
				}
				
				if (empty($route)) {
					if (isset($segment['app'])) {
						$route[]	= $segment['app'];
					}
					$route[]	= isset($segment['view']) ? $segment['view'] : 'default';
					$route[]	= isset($segment['task']) ? $segment['task'] : '';
					if (isset($segment['id'])) {
						if (strpos($segment['id'], ':') !== false) {
							list($id, $alias)	= explode(':', $segment['id']);
							$route[]	= $id;
							$route[]	= $alias;
						}
						else {
							$route[]	= $segment['id'];
						}
					}
				}
				
				$route		= implode('/', $route);
				$sef_url	= $config->baseURL . $route;
				
				// save redirection link
				if ($save_url) {
					if (isset($uri_vars['path']) && $uri_vars['path'] == 'index.php') {
						$url	= $config->baseURL . $url;
					}
			
					$old_url	= str_replace($config->baseURL, '', $url);
					if (($saved_url = self::saveRedirection($old_url, $route)) !== false) {
						$sef_url	= $config->baseURL . $lang_prefix . $saved_url;
					}					
				}
				
				$url	= $sef_url . $params;
			}
			else {
				$url	= $config->baseURL . $lang_prefix;
				$url	.= $params;
			}
		}
		else {
			// create param link
			$params	= empty($params) ? '' : ($params{0} != '#' ? (strpos($url,'?')!==false ? '&' : '?') : '') . $params;
		
			// add base URL
			if (strpos($url, 'http') === false) {
				$url	= $config->baseURL . $url;
			}
			
			$url	.= $params;
		}
		
		// force ssl
		if ($force_ssl && !self::_isLocal()) {
			$uri	= explode(':', $url);
			if (!empty($uri) && $uri{0} == 'http') {
				$url	= str_replace('http://', 'https://', $url);
			}
		}
		
		return $url;
	}
	
	/**
	Create a SEF routing table
		@param $segment array
		@public
	**/
	static public function buildSEFRoute( $base, $buffer )
	{
		return $buffer;
		
		// smart detection commented out - slows page loading
		/*
		$uri_vars	= parse_url($base);
		$basepath	= $uri_vars['host'].$uri_vars['path'];
		
		$i			= strlen($basepath);
		if ($basepath{$i - 1} != '/') {
			$basepath	.= '/';
		}
		$basepath	.= 'index.php';
	
		$regex	= "#((http|https|ftp)://$basepath\?.*?[(\"\')])#i";
		
		$callback_func	= '
			if (!empty($matches[1])) {
				return URL::_($matches[1]);
			}
			
			return $matches[1];
		';
		$buffer	= preg_replace_callback($regex, create_function('$matches', $callback_func), 
			$buffer);
		
		return $buffer;
		*/
	}
	
	/**
	Parse SEF routing
		@params $uri array
		@public
	**/
	static public function parseSEFRoute()
	{
		return self::_parseRoute();
	}
	
	/**
	Create SEF title
		@param $title string
		@param $id int
		@public
	**/
	static public function SEFTitle( $title, $id=null )
	{
		//mb_internal_encoding("utf-8");
		
		$title	= trim( trim(stripslashes(html_entity_decode($title))) );
		
		// clean-up 1st pass
		$title	= str_replace('&', 'and', $title);
		
		//$title	= preg_replace('/[^a-zA-Z0-9_\- ]/u', '', $title);
		$title	= preg_replace('/[^a-zA-Z0-9_\- ]$/u', '', $title);
		$title	= preg_replace( '#\$([0-9]*)#', '\\\$${1}', $title);
		$title	= preg_replace('/\s+/', '-', $title );
		
		while(strpos($title, '--') !== false) {
			$title	= str_replace('--', '-', $title);
		}
		
		if ($id) {
			return $id . ($title=='-'||empty($title) ? '' : ':'. $title);
		}
		else {
			return $title;
		}
	}
	
	/**
	Get url path
		@public
	**/
	static function getURI()
	{
		$__app		=& Factory::getApplication();
		$config		= $__app->get('config');
		
		$root_path	= parse_url($config->baseURL);
	
		$uri		= new stdclass();
		$uri->_raw	= (!empty($_SERVER['HTTPS']) || $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$uri->_url	= $_SERVER['REQUEST_URI'];
		
		if ($root_path['path'] == '/' && @$uri->_url[0] == '/') {
			$uri->_url	= substr($uri->_url, 1);
		}
		else {
			$uri->_url	= str_replace($root_path['path'], '', $uri->_url);
		}
		
		return $uri;
	}
	
	/**
	Fix content buffer relative path
		@param $base string
		@param $buffer string
		@public
	**/
	static public function tidy_path($base, $buffer)
	{
		// index
		$buffer	= preg_replace('/([\"\'])\/(index.php)/', '$1'.$base.'$2', $buffer);
		
		// scripts/styles
		$buffer	= preg_replace('/\b(href|src)\b=([\"\'])\//', '$1=$2'.$base, $buffer);
		
		// backgrounds
		$buffer	= preg_replace('/(background\:url\()\//', '$1'.$base, $buffer);
		
		// remove index.php
		$base_str	= str_replace('/', '\/', $base);
		$buffer	= preg_replace('/'.$base_str.'index.php/', $base, $buffer);
		
		return $buffer;
	}
	
	/**
	Set url redirection
		@param $url string
		@param $use_SEF boolean
		@param $params string
		@param $force_ssl boolean
		@public
	**/
	static function redirect( $url, $use_SEF=true, $params='', $force_ssl=false )
	{
		if ($use_SEF) {
			$url	= URL::_($url, $params);
		}
		
		// force ssl
		$uri	= explode(':', $url);
		if (!empty($uri)) {
			if ($force_ssl && !self::_isLocal()) {
				if ($uri{0} == 'http') {
					$url	= str_replace('http://', 'https://', $url);
				}
			}
			// remove https
			else if ($uri{0} == 'https') {
				$url	= str_replace('https://', 'http://', $url);
			}
		}
	
		if (headers_sent()) {
			echo "<script>document.location.href='$url';</script>";
		}
		else {
			header("Location: " . $url);
		}
	}
	
	/**
	Get redirection table mapping
		@param $newurl string
		@public
	**/
	static function getRedirection( $newurl )
	{
		if (empty($newurl)) {
			return false;
		}
	
		$db	= Factory::getDBO();
	
		$query	= "SELECT oldurl"
		."\n FROM {TABLE_PREFIX}_redirection"
		."\n WHERE newurl = " . $db->Quote($newurl)
		."\n LIMIT 1"
		;
		$db->query($query);
		
		return $db->result();
	}
	
	/**
	Determine if uri is in secured layer
		@public
	**/
	static function isSSL()
	{
		$https			= Request::getVar('HTTPS', null, 'SERVER');
		$server_port	= Request::getVar('SERVER_PORT', 80, 'SERVER');
		
		return !($https <> 'on' || $server_port == '80');
	}
	
	/**
	Parse URL routing
		@param $url string
		@private
	**/
	private static function _parseRoute( $url=null )
	{
		$segments	= array();
		
		if ($url) {
			$uri	= self::_buildURIFromURL($url);
		}
		else {
			$uri	= URL::getURI();
		}
		
		if (strpos($uri->_url, 'index.php?') !== false) {
			// non-SEF
			return $segments;
		}
		else {
			// check for language settings
			self::_parseLanguageOption($uri);
		
			// parse additional arguments
			@list($uri->_url, $args)	= explode('?', $uri->_url);
			
			if ($uri->_url == 'index.php') {
				$uri->_url	= '';
			}
		}
		
		$SEF_URL_path	= explode('/', $uri->_url);

		// get app from url
		$app		= $SEF_URL_path{0};
		
		// validate path
		$app_path	= PATH_APPLICATIONS .DS. $app;
		if (!is_dir($app_path)) {
			$app	= Request::getVar('app', 'default');
		}
		
		// get SEF implementation per app
		$appSEFName		= 'App' . ucfirst($app) .'SEF';
		
		$appSEFPath		= PATH_APPLICATIONS .DS. $app .DS. 'sef.php';
		if (file_exists($appSEFPath) && is_file($appSEFPath)) {
			require_once( $appSEFPath );
			
			$appSEFName		= 'App' . ucfirst($app) .'SEF';
			$appSEFClass	= new $appSEFName();
			
			if (is_subclass_of($appSEFClass, 'SEF')) {
				$appSEFTask		= 'ParseSEFRoute';
				if (method_exists($appSEFClass, $appSEFTask)) {
					// execute task
					$segments	= $appSEFClass->$appSEFTask($uri);
					
					// use default values
					if ($segments === false) {
						foreach ($SEF_URL_path as $i=>$r) {
							switch($i) {
								case 0:
									$segments['app']	= $r;
									break;
								case 1:
									$segments['view']	= $r;
									break;
								case 2:
									$segments['task']	= $r;
									break;
								case 3:
									$segments['id']	= $r;
									break;
							}
						} // foreach
					}
				} // method_exists
			}
		}
		else {
			$SEF_URL_path	= (empty($SEF_URL_path{0}) || $SEF_URL_path{0} == 'index.php' ? null : $SEF_URL_path);
			
			// default SEF implementation
			if (!empty($SEF_URL_path)) {
				$sk	= explode('|', 'app|view|task');
			
				foreach($SEF_URL_path as $i=>$segment_item) {
					$segments[ $sk{$i} ]	= $segment_item;
				}
			}
		}
		
		// if empty, get map from redirection table
		if (empty($segments)) {
			$segments	= array();
		
			$url		= self::getRedirection( @$uri->_url );
			$uri_vars	= parse_url($url);
			
			if (isset($uri_vars['query']) && !empty($uri_vars['query'])) {
				$qvars	= preg_split("/[\?&]+/", $uri_vars['query']);
				
				foreach($qvars as $qvar) {
					@list($k, $v)	= explode('=', $qvar);
					$segments[$k]	= $v;
				}
			}
		}
		
		// assign variables
		foreach ($segments as $k=>$v) {
			Request::setVar($k, $v);
		}
		
		// get count
		$is_valid	= true;
		if (!empty($uri->_url)) {
			$is_valid	= count($segments);
		}
		
		return $is_valid;
	}
	
	/** 
	Create URI object from url
		@param $url string
		@private
	**/
	private static function _buildURIFromURL( $url )
	{
		$__app		=& Factory::getApplication();
		$config		= $__app->get('config');
		
		$root_path	= parse_url($config->baseURL);
		$url_path	= parse_url($url);
	
		$uri		= new stdclass();
		$uri->_raw	= $url;
		$uri->_url	= $url_path['path'];
		
		if ($root_path['path'] == '/' && @$url[0] == '/') {
			$uri->_url	= substr($uri->_url, 1);
		}
		else {
			$uri->_url	= str_replace($root_path['path'], '', $uri->_url);
		}
		
		return $uri;
	}
	
	/** 
	Extract language code from uri
		@param $uri object
		@private
	**/
	private static function _parseLanguageOption( &$uri )
	{
		$elems	= explode('/', $uri->_url);
		if ($elems) {
			// get language packs
			$lang_codes	= I18N::getList();
			
			if (in_array($elems[0], array_keys($lang_codes))) {
				Request::setVar('language', $elems[0]);
				
				// omit lang code from url
				$uri->_raw	= str_replace("/{$elems[0]}", '', $uri->_raw);
				$uri->_url	= str_replace("{$elems[0]}/", '', $uri->_url);
			}
		}
	}
	
	/** 
	Save redirection uri to table
		@param $oldurl string
		@param $newurl string
		@private
	**/
	private static function saveRedirection( $oldurl, $newurl )
	{
		$db		=& Factory::getDBO();
		
		// check if exists
		// pass 1
		$query	= "SELECT `newurl`"
		."\n FROM {TABLE_PREFIX}_redirection"
		."\n WHERE `oldurl` = " . $db->Quote($oldurl)
		;
		$db->query($query);
		$result	= $db->result();
		
		if (!empty($result)) {
			// update on recent change
			if ($result <> $newurl) {
				$query	= "UPDATE {TABLE_PREFIX}_redirection"
				."\n SET `newurl` = " . $db->Quote($newurl)
				."\n WHERE `oldurl` = " . $db->Quote($oldurl)
				;
				$db->query($query);
				
				$result	= $newurl;
			}
			
			return $result;
		}
		
		// pass 2
		$query	= "SELECT count(*)"
		."\n FROM {TABLE_PREFIX}_redirection"
		."\n WHERE `newurl` LIKE " . $db->Quote( "{$newurl}%" )
		;
		$db->query($query);
		$found	= (int)$db->result();
		
		// increment
		if ($found) {
			$newurl	= $newurl . "-{$found}";
		}
		
		/**/
		// 11/09/2012
		$values	= array(
			$db->Quote($newurl),
			$db->Quote($oldurl)
		);
		$query	= "INSERT IGNORE INTO {TABLE_PREFIX}_redirection(`newurl`,`oldurl`)"
		."\n VALUES(" .implode(',', $values). ")"
		;
		$db->query($query);
		
		if ($found) {
			return $newurl;
		}
		
		return false;
	}
	
	/**
	Determine if system is in local machine
		@private
	**/
	private static function _isLocal()
	{
		//return (in_array($_SERVER['HTTP_HOST'], array('127.0.0.1', 'localhost')));
		
		$loops	= array(
			'127.0.0.1', 
			'localhost'
		);
		
		foreach ($loops as $loop) {
			if (strpos($_SERVER['HTTP_HOST'], $loop) !== false) {
				return true;
			}
		}
		
		return false;
	}
}