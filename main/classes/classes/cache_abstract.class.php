<?php
/*
* $Id: cache_abstract.class.php
* Caching system abstract class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

abstract class Cache_Abstract
{
	/**
	Fetch cache
		@param $key string
		@abstract
	**/
	abstract function fetch( $key );
	/**
	Store cache
		@param $key string
		@param $data mixed
		@abstract
	**/
	abstract function store( $key, $data );
	/**
	Delete cache
		@param $key string
	**/
	abstract function delete( $key );
}
