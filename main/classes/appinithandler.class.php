<?php
/*
* $Id: app_init.class.php
* Component initialization handler
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

class AppInitHandler
{
	protected $_name		= '';
	protected $_appName		= '';
	
	/**
	Class constructor
		@param $appName string
		@public
	**/
	function __construct( $appName='' )
	{
		// get handler name
		if ( preg_match( '/App(.*)/i', get_class($this), $match) ) {
			$this->_name	= strtolower( $match[1] );
		}
		
		// set application name
		$this->_appName		= $appName;
		
		if( empty($this->_appName) ) {
			$this->_appName		= Request::getVar('app', 'default');
		}
	}
	
	function __init()
	{
		return true;
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
		if( file_exists($modelPath) && is_file($modelPath) ) {
			require_once( $modelPath );
			
			$modelClassName	= ucfirst($this->_appName) .'Model'. ucfirst($model_name);
			$model			= new $modelClassName();
		}
		
		return $model;
	}
}
