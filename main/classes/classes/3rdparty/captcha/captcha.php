<?php
/*
* $Id: captcha.php, version 0.1.052911
*
* Captcha base class
*/

defined('_PRIVATE') or die('Direct access not allowed');

class Captcha
{
	protected $_lib;

	/**
	Class constructor
		@public
	**/
	function __construct( $lib=null )
	{
		$this->_lib	= $lib;
	}
	
	function load()
	{
		switch($this->_lib) {
			case 'hkcaptcha':
				include_once 'hkcaptcha.php';
			
				$captcha	= new HKCaptcha();
				return $captcha;
			
				break;
		}
		
		return null;
	}
}