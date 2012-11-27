<?php
/**
 * $Id: framework.php
 * @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

// autoload classes
set_include_path( PATH_LIBRARIES .DS. 'classes' );
spl_autoload_register(null, false);
spl_autoload_extensions('.class.php');
spl_autoload_register();

// load configuration file
$configFile	= BASE_PATH .DS. 'config.php';
if( !file_exists($configFile) ) {
	die( 'Configuration file not found.' );
}

// load required
require_once( $configFile );
require_once( 'application.php' );

// set timezone
$oldLevel	= error_reporting(0);
$tz			= date_default_timezone_get();
error_reporting($oldLevel);
date_default_timezone_set($tz);

//
// Factory class
//
final class Factory 
{
	/**
	Get core application class
		@public
	**/
	static function &getApplication()
	{
		static $app;
		
		// create if don't exists
		if ( !is_object($app) ) {
			// single app instance only
			if( isset($GLOBALS['_globalapp']) ) {
				$app	= $GLOBALS['_globalapp'];
			}
			else {
				$app	= new Application();
				
				// config
				$config	=& Factory::getConfig();
				$app->set('config', $config);
				// authentication
				$auth	=& Factory::getAuth();
				$app->set('auth', $auth);
				
				// set our timezone
				if (isset($config->timezone)) {
					@list($tz_script, $tz_db)	= explode('|', $config->timezone);
					date_default_timezone_set($tz_script);
				}
				
				$GLOBALS['_globalapp']	= $app;
			}
		}

		return $app;
	}
	
	/**
	Load application configuration file
		@public
	**/
	static function &getConfig()
	{
		static $config;
		
		// create if don't exists
		if ( !is_object($config) ) {
			$vars	= get_class_vars('Config');
			$config	= new stdclass();

			// map to object
			if( !empty($vars) ) {
				foreach($vars as $k=>$v) {
					$config->$k	= $v;
				}
			}
			
			// validate required
			if( !isset($config->dsn) ) {
				$config->dsn	= '';
			}
			
			// raw
			$config->__raw	= new Config();
			
			// parse host scheme
			$http_scheme	= isset($_SERVER['HTTPS']) || ($_SERVER['SERVER_PORT'] == '443') ? 'https' : 'http';
			$config->baseURL	= preg_replace('/<scheme\/>/i', $http_scheme, $config->baseURL);
			$config->cacheURL	= preg_replace('/<scheme\/>/i', $http_scheme, $config->cacheURL);
			// parse host alias
			$config->baseURL	= preg_replace('/<host\/>/i', $_SERVER['HTTP_HOST'], $config->baseURL);
			$config->cacheURL	= preg_replace('/<host\/>/i', $_SERVER['HTTP_HOST'], $config->cacheURL);
		}
		
		return $config;
	}

	/**
	Get database object
		@public
	**/
	static function &getDBO( $force_reload=false )
	{
		static $dbo;
		
		if ($force_reload) {
			// re-init db instance
			$dbo	= null;
		}

		// create if don't exists
		if (!is_object($dbo)) {
			$config	= Factory::getConfig();
			
			// extract timezone for DB
			@list($tz_script, $tz_db)	= explode('|', $config->timezone);
			
			$storage	= new storage();
			// set timezone
			$storage->set_timezone($tz_db);
			// load db instance
			$dbo		= $storage->load($config->dsn);
			if (!$dbo) {
				die( 'Cannot connect to database.' );
			}
		}

		return $dbo;
	}
	
	/**
	Get user authentication
		@public
	**/
	static function &getAuth()
	{
		static $auth;
	
		// create if don't exists
		if ( !is_object($auth) ) {
			$auth = new Auth();
		}
		
		return $auth;
	}
	
	/**
	Get ACL
		@public
	**/
	static function &getACL()
	{
		static $acl;
	
		// create if don't exists
		if ( !is_object($acl) ) {
			$acl = new ACL();
		}
		
		return $acl;
	}
	
	/**
	Get Caching system
		@public
	**/
	static function &getCache()
	{
		static $cache;
	
		// create if don't exists
		if ( !is_object($cache) ) {
			// get cache type
			$config	=& Factory::getConfig();
			
			switch (@$config->cache_type) {
				default:
					// file
					$cache	= new FileCache( $config->cache_ttl );
			}
		}
		
		return $cache;
	}
	
	/**
	Get Logging system
		@public
	**/
	static function &getLogging( $unique_id=null )
	{
		static $logging;
	
		// create if don't exists
		if ( !is_object($logging) ) {
			// get logging type
			$config	=& Factory::getConfig();
			
			switch (@$config->log_type) {
				default:
					// file
					$logging	= new FileLogging($unique_id);
			}
		}
		
		return $logging;
	}
	
	/* 18/May/2012
	** Add mobile detection
	*/
	static function &getMobileDetector()
	{
		static $mobile_detect;
	
		// create if don't exists
		if ( !is_object($mobile_detect) ) {
			require_once( PATH_CLASSES .DS. '3rdparty' .DS. 'Mobile_Detect.php' );
			
			// initialize
			$mobile_detect = new Mobile_Detect();
		}
		
		return $mobile_detect;
	}
}

//
// Variable request access
// 
final class Request
{
	/**
	Get request method
		@public
	**/
	static function getMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	Get request variable
		@param $name string
		@param $default mixed
		@param $request_method string
		@public
	**/
	static function getVar($name, $default=null, $request_method='REQUEST')
	{
		if (empty($request_method)) {
			$request_method	= $_SERVER['REQUEST_METHOD'];
		}

		// Get the value by request method
		$request_method	= strtoupper($request_method);
		switch($request_method) {
			case 'GET':
				$data		=& $_GET;
				break;
			case 'POST':
				$data		=& $_POST;
				break;
			case 'FILES':
				$data		=& $_FILES;
				break;
			case 'COOKIE' :
				$data		=& $_COOKIE;
				break;
			case 'ENV':
				$data		=& $_ENV;
				break;
			case 'SERVER':
				$name		= strtoupper($name);
				$data		=& $_SERVER;
				break;
			default:
				$data		=& $_REQUEST;
				break;
		}
		
		$value	= (isset($data[$name]) && $data[$name] !== null) ? $data[$name] : $default;
		$value	= empty($value) ? $default : $value;
		$value	= self::_clean($value);
		
		return $value;
	}

	/**
	Set request variable
		@param $name string
		@param $value mixed
		@param $request_method string
		@public
	**/
	static function setVar($name, $value=null, $request_method='')
	{
		$old_value	= (isset($_REQUEST[$name]) && $_REQUEST[$name] !== null) ? $_REQUEST[$name] : null;
		$old_value	= self::_clean($old_value);
		
		if ( empty($request_method) ) {
			$request_method			= $_SERVER['REQUEST_METHOD'];
		}
		
		// Get the value by request method
		$request_method	= strtoupper($request_method);
		switch($request_method) {
			case 'GET':
				$_GET[$name]		= $value;
				$_REQUEST[$name]	= $value;
				break;
			case 'POST':
				$_POST[$name]		= $value;
				$_REQUEST[$name]	= $value;
				break;
			case 'FILES':
				$_GET[$name]		= $value;
				break;
			case 'COOKIE' :
				$_COOKIE[$name]		= $value;
				$_REQUEST[$name]	= $value;
				break;
			case 'ENV':
				$_ENV[$name]		= $value;
				break;
			case 'SERVER':
				$name				= strtoupper($name);
				$_SERVER[$name]		= $value;
				break;
		}
		
		return $old_value;
	}

	static private function _clean($var)
	{
		//return is_array($var) ? array_map(array($this, '_clean'), $var) : str_replace("\\", "\\\\", htmlspecialchars((get_magic_quotes_gpc() ? stripslashes($var) : $var), ENT_QUOTES)); 
		//return is_array($var) ? @array_map('Request::_clean', $var) : str_replace("\\", "\\\\", htmlspecialchars((get_magic_quotes_gpc() ? stripslashes($var) : $var), ENT_QUOTES)); 

		if( !is_array($var) ) {
			$var	= get_magic_quotes_gpc() ? stripslashes($var) : $var;
			$var	= htmlspecialchars($var, ENT_QUOTES);
			$var	= str_replace("\\", "\\\\", $var); 
		}
			
		return $var;
	}
}