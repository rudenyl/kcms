<?php
/*
* $Id: sef.class.php
* SEF url routing manipulation base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

class SEF
{
	/**
	Class constructor
		@public
	**/
	function __construct() {}
	
	/**
	Create a SEF routing table
		@param $segment array
		@public
	**/
	function BuildSEFRoute( &$segment ) {}
	
	/**
	Parse SEF routing
		@params $uri array
		@public
	**/
	function ParseSEFRoute( &$uri ) {}
	
	/**
	Save URL tag
		@public
	**/
	function saveURL()
	{
		return false;
	}
}
