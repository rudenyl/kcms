<?php
/*
* $Id: logging_abstract.class.php
* Logging system abstract class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

abstract class Logging_Abstract
{
	/**
	Get log file reference
		@abstract
	**/
	abstract function fetch();
	/**
	Log message
		@param $msg string
		@param $date datetime
		@abstract
	**/
	abstract function store( $msg, $date=null );
	/**
	Delete log file reference
		@abstract
	**/
	abstract function delete();
}
