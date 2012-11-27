<?php
/*
* $Id: view.class.php, version 0.1.172011
*
* View base class
*/

defined('_PRIVATE') or die('Direct access not allowed');

class View
{
	protected $_name		= '';
	protected $_appName		= '';
	
	protected $_model		= null;
	protected $_template	= '';
	protected $_pluginMgr	= null;
	protected static $_data	= array();

	/**
	Class constructor
		@public
	**/
	function __construct( $appName='' )
	{
		// get view name
		if ( preg_match( '/View(.*)/i', get_class($this), $match) ) {
			$this->_name 	= strtolower($match[1]);
		}
		
		// get application name
		$this->_appName		= $appName;
		
		if( empty($this->_appName) ) {
			$this->_appName		= Request::getVar('app', 'default');
		}
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
		if( isset(self::$_data[$sig][$key]) ) {
			$value	= self::$_data[$sig][$key];
		}
		
		// set default value
		if( empty($value) && $default_value ) {
			$value	= $default_value;
		}
		
		return $value;
	}
	
	/**
	Set view layout
		@param $layout string
		@public
	**/
	public function setLayout( $layout )
	{
		$this->_template	= $layout;
	}
	
	/**
	Get view layout
		@public
	**/
	public function getLayout()
	{
		return strtolower( $this->_template );
	}
	
	/**
	Display the template
		@param $layout string
		@public
	**/
	public function display( $layout='' )
	{
		if( empty($layout) ) {
			$layout	= $this->_template;
		}
		
		$html	= $this->loadTemplate( $layout );
		echo $html;
	}
	
	/**
	Set view model
		@param $model string
		@public
	**/
	public function setModel( $model )
	{
		$this->_model	= $model;
	}
	
	/**
	Get view model
		@public
	**/
	public function &getModel( $name='' )
	{
		$model	= null;
	
		if( !empty($name) ) {
			$modelPath	= PATH_APPLICATIONS .DS. $this->_appName .DS. 'models' .DS. $name.'.php';
			
			// load model
			if( file_exists($modelPath) && is_file($modelPath) ) {
				require_once( $modelPath );
				
				$this_class_name	= get_class($this);
				
				$modelClassName	= ucfirst($this->_appName) .'Model'. ucfirst($name);
				$model			= new $modelClassName();
				
				if( is_subclass_of($model, 'Model')  ) {
					return $model;
				}
				else {
					return false;
				}
			}
		}
	
		if( isset($this->_model) ) {
			$model	= $this->_model;
		}
		
		return $model;
	}
	
	/**
	Set plugins
		@public
	**/
	public function setPlugins( $plugins )
	{
		$this->_pluginMgr	= $plugins;
	}
	
	/**
	Get plugins
		@public
	**/
	public function getPlugins()
	{
		if ($this->_pluginMgr) {
			// add model reference
			$model	=& $this->getModel();
			$this->_pluginMgr->setModel($model);
			
			// add view reference
			$this->_pluginMgr->setView($this);
		}
		
		return $this->_pluginMgr;
	}

	/**
	Load view template
		@param $layout string
		@public
	**/
	public function loadTemplate( $layout='default' )
	{
		$html	= '';
		
		if( is_subclass_of($this, 'View') ) {
			$tpl_path	= PATH_APPLICATIONS .DS. $this->_appName .DS. 'views' .DS. $this->_name .DS. 'tmpl' .DS. $layout.'.php';

			// load layout
			if( file_exists($tpl_path) && is_file($tpl_path) ) {
				ob_start();
				
				include( $tpl_path );
				
				$html	= ob_get_clean();
			}
		}
		
		return $html;
	}
}
