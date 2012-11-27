<?php
/*
* $Id: plugins.class.php, version 0.1.020711
*
* Plugin base class
*/

defined('_PRIVATE') or die('Direct access not allowed');

class Plugin
{
	protected $_items	= array();
	protected $_model	= null;
	protected $_view	= null;

	/**
	Class constructor
		@public
	**/
	function __construct()
	{
	}
	
	/**
	Add a plugin
		@param $plugin mixed
		@public
	**/
	public function addPlugin( $plugins )
	{
		if( !is_array($plugins) ) {
			if( !is_object($plugins) ) return false;
		
			$this->_items[]		= $plugins;
		}
		
		foreach($plugins as $plugin) {
			if( is_object($plugin) ) {
				$this->_items[]	= $plugin;
			}		
		}
	}
	
	/**
	Set plugin model
		@param $model string
		@public
	**/
	public function setModel( $model )
	{
		$this->_model	= $model;
	}
	
	/**
	Get plugin model
		@public
	**/
	public function &getModel()
	{
		$model	= null;
	
		if( isset($this) ) {
			if( isset($this->_model) ) {
				$model	= $this->_model;
			}
		}
		else {
			if( isset(self::$_model) ) {
				$model	= self::$_model;
			}
		}
		
		return $model;
	}
	
	/**
	Set plugin view 
		@param $view string
		@public
	**/
	public function setView( $view )
	{
		$this->_view	= $view;
	}
	
	/**
	Get plugin view
		@public
	**/
	public function &getView()
	{
		$view	= null;
	
		if( isset($this->_view) ) {
			$view	= $this->_view;
		}
		
		return $view;
	}
	
	/**
	Load plugin event
		@public
	**/
	public function load()
	{
		$argv =& func_get_args();
		
		if( count($argv) < 1 ) return;
		
		$method	= $argv[0];
		array_shift($argv);

		// iterate through plugins
		foreach($this->_items as $plugin) {
			$classname	= get_class($plugin);
			
			if( is_callable( array($classname, $method) ) ) {
				$args		= array();
				
				$args[]	=& $this->getModel();
				$args[]	=& $this->getView();
				
				// add args
				for($i=0, $n=count($argv); $i<$n; $i++) {
					$args[]	= $argv[$i];
				}
				
				call_user_func_array( array($classname, $method), $args );
			}
		}
	}
}
