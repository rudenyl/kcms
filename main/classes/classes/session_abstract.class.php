<?php
/*
* $Id: session_abstract.class.php
* Custom session handling implementation abstract class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

abstract class Session_Abstract
{
	/**
	Initialize object
		@abstract
	**/
	abstract function init();
	/**
	Load session
		@param $save_path string
		@param $sess_name string
		@abstract
	**/
	abstract function open( $save_path, $sess_name );
	/**
	Close the session
		@abstract
	**/
	abstract function close();
	/**
	Fetch session data
		@param $sess_id string
		@abstract
	**/
	abstract function read( $sess_id );
	/**
	Save session data
		@param $save_path string
		@param $sess_name string
		@abstract
	**/
	abstract function write( $sess_id, $sess_data );
	/**
	Destroy the session
		@param $sess_id string
		@abstract
	**/
	abstract function destroy( $sess_id );
	/**
	Garbage collection
		@param $maxlife integer
		@abstract
	**/
	abstract function gc( $maxlife );
}
