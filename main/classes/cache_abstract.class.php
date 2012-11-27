<?php
/*
* $Id: cache_abstract.class.php
*
* Caching system abstract class
*/

defined('_PRIVATE') or die('Direct access not allowed');

abstract class Cache_Abstract
{
	abstract function fetch( $key );
	abstract function store( $key, $data );
	abstract function delete( $key );
}
