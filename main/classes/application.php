<?php
/**
 * $Id: application.class.php, version 0.1.172011
 * Application core class
 * @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class Application 
{
	protected static $global;

	/**
	Class constructor
		@public
	**/
	function __construct() 
	{
		// set global session identification
		$config	=& Factory::getConfig();
		
		switch (@$config->session_type) {
			case 'db':
				$sess_db	= new SessionDBHandler();
				$sess_db->init();
				break;
		}
		
		session_start();
		self::$global['SESSION_ID']	= session_id();
		
		// init content buffer
		self::$global['RESPONSE']	= null;
	}
	
	/**
	Set class defined variable
		@param $key string
		@param $value mixed
		@param $head boolean
		@public
	**/
	static public function set( $key, $value=null, $head=false, $raw=false )
	{
		$allowed_keys	= explode('|',
			'base|title|meta|styles|js|config|crumbs|auth'
		);
		
		if (in_array($key, $allowed_keys)) {
			$key	= strtoupper($key);
			$arr	= explode('|', 'META|STYLES|JS|CRUMBS' );	// array`ed item keys
			
			if (in_array($key, $arr)) {
				if (!isset(self::$global[$key])) {
					self::$global[$key]	= array();
				}
				
				$values	= array();
				if (!is_array($value)) {
					$values[]	= $value;
				}
				else {
					$values		= array_merge($values, $value);
				}
				
				foreach ($values as $value) {
					// process key settings
					switch ($key) {
						case 'JS':
							if (!$raw) {
								$value	= "<script type=\"text/javascript\" src=\"$value\"></script>";
							}
							break;
						case 'STYLES':
							if (!$raw) {
								$value	= "<link rel=\"stylesheet\" href=\"$value\" type=\"text/css\" />";
							}
							break;
					}
				
					if ($head) {
						array_unshift(self::$global[$key], $value);
					} 
					else {
						array_push(self::$global[$key], $value);
					}
				}
			}
			else {
				self::$global[$key]	= $value;
			}
		}
	}
	
	/**
	Get class defined variable
		@param $key string
		@param $default_value mixed
		@public
	**/
	static public function get( $key, $default_value=null )
	{
		$value	= null;
	
		if (isset(self::$global[strtoupper($key)])) {
			$value	= self::$global[strtoupper($key)];
			
			if (empty($value)) {
				$value	= $default_value;
			}
		}
			
		return $value;
	}

	/**
	Set application-wide notification
		@public
		@param $text string
	**/
	static function setNotification( $text='', $id='success' )
	{
		@session_start();
		
		if (empty($id)) {
			$id	= 'success';
		}
		$_SESSION[$id]['_app-notification']	= $text;
	}
	
	/**
	Get application-wide notification
		@public
	**/
	static function getNotification( $id='success' )
	{
		@session_start();
		
		if (empty($id)) {
			$id	= 'success';
		}
		if (isset($_SESSION[$id]['_app-notification'])) {
			$notification	= $_SESSION[$id]['_app-notification'];
			
			// clear on last fetch
			$_SESSION[$id]['_app-notification']	= null;
			unset($_SESSION[$id]['_app-notification']);
			
			return $notification;
		}
		
		return null;
	}
	
	/**
	Get current session id
		@public
		@param $hash_type string
	**/
	static function getSessionID( $hash_type='md5' )
	{
		$session_id	= self::get('SESSION_ID');
		
		switch ($hash_type) {
			case 'none':
				break;
				
			case 'sha1':
				$session_id	= sha1($session_id);
				break;
				
			default:
				$session_id	= md5($session_id);
				break;
		}
		
		return $session_id;
	}
	
	/**
	Get controller
		@param $name string
		@private
	**/
	private function &getController( $app, $name ) 
	{
		$found			= false;
		$controller		= null;
		$controllerPath	= PATH_APPLICATIONS .DS. $app .DS. 'controllers' .DS. $name.'.php';
		
		// load controller
		if (!($found = file_exists($controllerPath) && is_file($controllerPath))) {
			// load default
			$name			= 'default';
			$controllerPath	= PATH_APPLICATIONS .DS. $app .DS. 'controllers' .DS. $name.'.php';
			
			$found			= (file_exists($controllerPath) && is_file($controllerPath));
		}
		
		if ($found) {
			// load controller
			require_once( $controllerPath );
			
			$controllerName	= ucfirst($app) .'Controller'. ucwords($name);
			$controller		= new $controllerName();
			
			// check base class
			if (!is_subclass_of($controller, 'Controller')) {
				return null;
			}
		}
		
		return $controller;
	}
	
	/**
	* Load component initialization handler
	*/
	private function &getAppInitHandler( $app )
	{
		$appInitHandler	= null;
		
		$appPath	= PATH_APPLICATIONS .DS. $app .DS. 'init.php';
		// load handler
		if (file_exists($appPath) && is_file($appPath)) {
			require_once( $appPath );
			
			$handlerName	= 'App'. ucwords($app);
			$appInitHandler	= new $handlerName();
			
			// check base class
			if (!is_subclass_of($appInitHandler, 'AppInitHandler')) {
				return null;
			}
		}
		
		return $appInitHandler;
	}
	
	/**
	Get template contents
		@param $name string
		@private
	**/
	private function &getTemplateBody() 
	{
		// set template
		$config	= $this->get('config');
		
		$template	= isset($config) ? $config->template : 'default';
		if ('component' === Request::getVar('format')) {
			$tpl_path	= PATH_TEMPLATES .DS. $template .DS. 'component.php';
		}
		else if ('custom' === Request::getVar('format')) {
			$tpl_path	= PATH_TEMPLATES .DS. $template .DS. 'custom.php';
		}
		else {
			$tpl_path	= PATH_TEMPLATES .DS. $template .DS. 'index.php';
		}
		
		ob_start();
		if (file_exists($tpl_path)) {
			include $tpl_path;
		}
		
		$html	= ob_get_clean();
		
		return $html;
	}
	
	/**
	Load application modules
		@private
	**/
	private function _loadModules()
	{
		// extract module tag
		preg_match_all('|<site:modules\[(.*)\]\/>|', self::$global['RESPONSE'], $matches);
		
		// load module files
		if (isset($matches[1]) && count($matches[1]) > 0) {
			foreach ($matches[1] as $mtxt) {
				$html		= '';
				$modules	= explode('|', $mtxt);
				
				if (count($modules) > 0) {
					foreach ($modules as $module) {
						// get params
						preg_match('/\{(.*)\}/', $module, $mparams);
						if (!empty($mparams)) {
							$module	= str_replace($mparams[0], '', $module);
							
							$mparams	= json_decode( $mparams[0] );
						}
					
						// get from storage
						$list	= ModuleHelper::_('position', $module);
						if ($list) {
							foreach ($list as $module_item) {
								if ($module_item->name == 'custom') {
									$html	.= ModuleHelper::render($module_item);
								}
								else {
									$module_item->params	= new Parameter($module_item->params);
									$html	.= $this->_loadModule($module_item->name, $module_item->params);
								}
							}
						}
						else {
							// file-based
							$html	.= $this->_loadModule($module, null, $mparams);
						}
					}
				}
				
				// display
				self::$global['RESPONSE']	= str_replace('<site:modules['.$mtxt.']/>', $html, self::$global['RESPONSE']);
			} // foreach
		}
	}
	/**
	Load application module item
		@private
	**/
	private function _loadModule( $module_name, $params=null, $module=null )
	{
		$modulePath	= PATH_MODULES .DS. $module_name .DS. $module_name .'.php';
		
		if (file_exists($modulePath) && is_file($modulePath)) {
			ob_start();
			
			// load module
			include( $modulePath );
			
			$html	= ob_get_clean();
			
			return $html;
		}
		
		return null;
	}
	
	/**
	Build the HTML head
		@private
	**/
	private function _getTemplateHead()
	{
		$head	= array();
		
		// default meta tags
		$this->set('meta', array(
				'<meta name="robots" content="index, follow" />',
				'<meta http-equiv="content-type" content="text/html; charset=utf-8" />'
			), true
		);
		
		// meta 
		if (isset(self::$global['META']) && is_array(self::$global['META'])) {
			$head	= array_merge($head, self::$global['META']);
		}
		
		// get title
		$config	= $this->get('config');
		$title	= $config->siteTitle;
		
		if (isset(self::$global['TITLE'])) {
			$title	= self::$global['TITLE'];
		}
		$title	= '<title>'.$title.'</title>';
		array_unshift($head, $title);
		
		// set base href
		$uri	= URL::getURI();
		if ($uri) {
			$base_url	= '<base href="' .$config->baseURL.$uri->_url. '" />';
			array_unshift($head, $base_url);
		}
		
		self::$global['HEAD']	= implode("\n", $head);
	}
	
	/**
	Parse script declarations
		@private
	**/	
	private function _parseScriptDeclarations()
	{
		// Scripts, styles
		preg_match('|<site:scripts\[(.*)\]\/>|', self::$global['RESPONSE'], $match);
		
		if (isset($match[1]) && !empty($match[1])) {
			$html			= array();
			$allowed_keys	= explode('|', 'JS|STYLES');
			$declarations	= explode(',', $match[1]);
			
			foreach ($declarations as $k) {
				$k	= strtoupper($k);
				if (in_array($k, $allowed_keys)) {
					if (isset(self::$global[$k])) {
						$values	= array_unique(self::$global[$k]);
						
						foreach ($values as $v) {
							$html[]	= $v;
						}
					}
				}
			}
			
			// display
			$html	= implode("\n", $html);
			self::$global['RESPONSE']	= str_replace('<site:scripts['.$match[1].']/>', $html, self::$global['RESPONSE']);
		}
	}
	
	/**
	Render page
		@private
	**/
	private function _render()
	{
		// can load template
		$load_template	= true;
	
		$config	= $this->get('config');
		
		// process request
		if ($config->SEFURL) {
			// process SEF request
			URL::parseSEFRoute();
		}
		
		$app		= Request::getVar('app', 'default');
		$view		= Request::getVar('view', 'default');
		
		// load component initialization handler
		if ($appInitHandler = $this->getAppInitHandler($app)) {
			// call init function
			if (method_exists($appInitHandler, '__init')) {
				$appInitHandler->__init();
			}
		}
		
		// get controller
		$controller	=& $this->getController($app, $view);
		// process 404's if controller empty
		if (empty($controller)) {
			if ($config->SEFURL && (isset($config->process404) && $config->process404)) {
				// get default app
				Request::setVar('app', 'default');
				Request::setVar('view', 'default');
				Request::setVar('task', '__404');
				
				$controller	=& $this->getController('default', 'default');
			}
		}
		
		$content_raw_data	= '';
		if ($controller) {
			ob_start();
			$controller->execute( Request::getVar('task') );
			$content_raw_data	= ob_get_clean();
			
			// display output for raw
			if ('raw' === Request::getVar('format')) {
				$load_template				= false;
				
				self::$global['RESPONSE']	= $content_raw_data;
			}
		}
		
		// this process is skipped when calling raw html format
		if ($load_template) {
			// load template body
			self::$global['RESPONSE']	= $this->getTemplateBody();
			
			// update content data	
			self::$global['RESPONSE']	= str_replace('<site:content/>', $content_raw_data, self::$global['RESPONSE']);
			
			// load modules
			$this->_loadModules();
		}
			
		// get script declarations
		$this->_parseScriptDeclarations();
		
		// get html head
		$this->_getTemplateHead();
		
		// clean up
		$array_site_keys	= array(
			'<site:head/>',
			'<site:content/>'
		);
		$array_site_key_replacements	= array(
			(isset(self::$global['HEAD']) ? self::$global['HEAD'] : ''),
			''
		);
		self::$global['RESPONSE']	= str_replace($array_site_keys, 
			$array_site_key_replacements, self::$global['RESPONSE']
		);
		
		/* process routing */
		// tidy paths
		self::$global['RESPONSE']	= URL::tidy_path($config->baseURL, self::$global['RESPONSE']);
		// SEF
		if ($config->SEFURL) {
			self::$global['RESPONSE']	= URL::buildSEFRoute($config->baseURL, self::$global['RESPONSE']);
		}

		// pack javascripts
		if (isset($config->packJS) && ($config->packJS !== false)) {
			$callback_func	= '
				$app			=& Factory::getApplication();
				$config			= $app->get("config");
				$pack_method	= $config->packJS;
				
				// set default
				$default	= $matches[0];
				$pack_js	= true;
				
				// get NOPACK clear tag
				preg_match(\'/pack="(.*?)"/\', $default, $nopack);
				if (!empty($nopack)) {
					$pack_types	= explode("|", "none|packer|minify");
					$pack_type	= $nopack[1];
					
					if (isset($pack_type) && in_array($pack_type, $pack_types)) {
						if ($pack_type != "none") {
							$pack_method	= $nopack[1];
						}
					
						$pack_js	= ($pack_type != "none");
					}
					
					// clear tag
					$default	= preg_replace(\'/pack="(.*?)"/\', "", $default);
				}
			
				if (!empty($matches[1]) && $pack_js) {
					// set placeholder
					$script	= str_replace($matches[1], "\n@script", $default);
					
					// pack
					$packedJS	= $matches[1];
					switch ($pack_method) {
						case "packer":
							$packer		= new JavaScriptPacker($matches[1]);
							$packedJS	= $packer->pack();
							break;
							
						case "minify":
							$packedJS	= JSMin::minify($matches[1]);
							break;
					}
					
					$packedJS	.= "\n";
					
					// remove hide tags
					if ($pack_method) {
						$packedJS	= str_replace(
							array("<!--", "//-->","-->"), 
							array("", "", ""),
							$packedJS
						);

						return str_replace("@script", $packedJS, $script);
					}
				}
				
				return $default;
			';
			
			self::$global['RESPONSE']	= preg_replace_callback('|<script[^>]*>(.*)<\/script>|sU', 
				create_function('$matches', $callback_func), 
				self::$global['RESPONSE']
			);
		}
		
		// compress
		if (isset($config->compress_output) && $config->compress_output === true) {
			// we're forcing output compression on
			ini_set('zlib.output_compression', 'On');
			
			self::$global['RESPONSE']	= $this->compress( self::$global['RESPONSE'] );
		}
		
		// show output
		echo self::$global['RESPONSE'];
	}
	
	/**
	Set page compression
		@public
	**/
	private function compress( $buffer )
	{
		if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			return $buffer;
		}

		// is it supported?
		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $buffer;
		}

		// headers sent already?
		if (headers_sent()) {
			return $buffer;
		}

		// encode now
		$gz_buffer	= gzencode($buffer, 4);

		$encoding	= null;
		if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
			$encoding = 'gzip';
		}
		if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false) {
			$encoding = 'x-gzip';
		}

		if ($encoding !== null) {
			header('Content-Encoding', $encoding);
			header('X-Content-Encoded-By', 'XRFW');
		}
		
		// we're not caching
		header('Expires', 'Mon, 1 Jan 1970 00:00:00 GMT', true);
		header('Last-Modified', gmdate("D, d M Y H:i:s") . ' GMT', true);
		header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
		header('Pragma', 'no-cache');

		return $gz_buffer;
	}
	
	/**
	Run the application
		@public
	**/
	public function run()
	{
		// close current session and store data
		session_write_close();
		
		// display content
		$this->_render();
	}
}