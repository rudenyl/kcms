<?php
/*
* $Id: controller.class.php, version 0.1.172011
* Controller base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

class Controller
{
	protected $_name		= '';
	protected $_appName		= '';
	protected $_pluginMgr	= null;
	
	protected static $_data	= array();

	/**
	Class constructor
		@public
	**/
	function __construct( $appName='' )
	{
		// get controller name
		if ( preg_match( '/Controller(.*)/i', get_class($this), $match)) {
			$this->_name	= strtolower( $match[1] );
		}
		
		// set application name
		$this->_appName		= $appName;
		
		if (empty($this->_appName)) {
			$this->_appName		= Request::getVar('app', 'default');
		}
		
		// load plugins
		$this->loadPlugins();
	}
	
	/**
	Set class variables
		@param $key string
		@param $value mixed
		@public
	**/
	public function set( $key, $value=null )
	{
		$sig	= md5( $this->_name.$this->_appName );
		self::$_data[$sig][$key]	= $value;
	}
	
	/**
	Get class variables
		@param $key string
		@param $value mixed
		@public
	**/
	public function get( $key, $default_value=null )
	{
		$value	= null;

		$sig	= md5( $this->_name.$this->_appName );
		if (isset(self::$_data[$sig][$key])) {
			$value	= self::$_data[$sig][$key];
		}
		
		// set default value
		if (empty($value) && $default_value) {
			$value	= $default_value;
		}
		
		return $value;
	}
	
	/**
	Execute controller task
		@param $task string
		@public
	**/
	public function execute( $task='' )
	{
		$not_allowed	= explode('|', '__construct|get|set|getmodel|getview|getplugins|execute');
		
		$task	= empty($task) ? 'display' : $task;
		
		if (!in_array($task, $not_allowed)) {
			// parse dot notation
			$params	= explode('.', $task);
			$task	= array_shift($params);
			
			// check if method exists
			if ( method_exists($this, $task) && $task <> 'display') {
				// execute task
				$this->$task( $params );
			}
			else {
				$this->display();
			}
		}
	}
	
	/**
	Get controller model
		@param $name string
		@return object
		@public
	**/
	public function &getModel( $name='' )
	{
		$model		= null;
		$model_name	= empty($name) ? $this->_name : $name;
		$modelPath	= PATH_APPLICATIONS .DS. $this->_appName .DS. 'models' .DS. $model_name.'.php';
		
		// load model
		if (file_exists($modelPath) && is_file($modelPath)) {
			require_once( $modelPath );
			
			$modelClassName	= ucfirst($this->_appName) .'Model'. ucfirst($model_name);
			$model			= new $modelClassName();
		}
		
		return $model;
	}
	
	/**
	Get plugins
		@public
	**/
	public function getPlugins()
	{
		// add model reference
		$model	=& $this->getModel();
		$this->_pluginMgr->setModel($model);
		
		// add view reference
		$view	=& $this->getView();
		$this->_pluginMgr->setView($view);
		
		return $this->_pluginMgr;
	}

	/**
	Get controller view
		@return object
		@public
	**/
	public function &getView()
	{
		// validate
		$view		= null;
		$viewPath	= PATH_APPLICATIONS .DS. $this->_appName .DS. 'views' .DS. $this->_name .DS. 'index.php';
		
		// load view
		if (file_exists($viewPath) && is_file($viewPath)) {
			require_once( $viewPath );
			
			$viewName	= ucfirst($this->_appName) .'View'. ucfirst($this->_name);
			$view		= new $viewName( $this->_appName );
			
			// Get/Create the model
			if ($model = &$this->getModel()) {
				$view->setModel($model);
			}
			
			// attach plugins
			$view->setPlugins($this->_pluginMgr);
		}
		
		return $view;
	}
	
	/**
	Set controller view
		@param $name string
		@public
	**/
	public function setView( $name )
	{
		$this->_name	= $name;
	}
	
	/**
	Render display view
		@param $layout string
		@public
	**/
	public function display( $layout=null )
	{
		// get view layout
		if ($layout === null) {
			$layout	= $this->get('layout', Request::getVar('layout', 'default'));
		}

		// get controller view
		$view	=& $this->getView();
		
		if ($view) {
			// Set the layout
			$view->setLayout($layout);
			
			// Display the view
			$view->display();
		}
	}
	
	/**
	Load plugins
		@private
	**/
	private function loadPlugins()
	{
		// set plugin handler
		$this->_pluginMgr	= new Plugin();
		$plugins			= array();
	
		$plugin_path	= PATH_APPLICATIONS .DS. $this->_appName .DS. 'plugins';
		
		// get files
		$files			= Files::getFolderFiles($plugin_path, 'php');
		if (($files === false) || count($files) < 1 ) return false;
		
		foreach($files as $i=>$file) {
			$filePath	= $plugin_path .DS. $file;
			// get file info
			$path_info	= pathinfo($filePath);
			
			$classname	= ucfirst($this->_appName). 'Plugin'. ucfirst($path_info['filename']);
			
			if (!class_exists($classname, false)) {
				require_once( $filePath );
				
				$class_obj	= new $classname;
				
				// is an Plugins sub-class?
				if (is_subclass_of($class_obj, 'Plugin')) {
					// get index
					// TODO: remove tagging if plugin is db-based
					$index		= (isset($class_obj->index) && (int)$class_obj->index ? $class_obj->index : 9999+$i);
				
					$plugins[$index]	= $class_obj;
				}
			}
			
			// sort
			ksort($plugins);
		} // foreach
		
		// add to list
		$this->_pluginMgr->addPlugin($plugins);
	}
}
