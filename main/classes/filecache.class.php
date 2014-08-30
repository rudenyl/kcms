<?php
/*
* $Id: filecache.class.php
* Caching system using filesystem
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

class FileCache extends Cache_Abstract
{
	protected $_ttl	= 60;

	/**
	Class constructor
		@public
	**/
	function __construct( $ttl=null )
	{
		// set cache TTL
		if ((int)$ttl < 1) {
			$ttl	= $this->_ttl;
		}
		
		$this->_ttl	= $ttl;
	}
	
	/**
	Fetch cache
		@param $key string
		@public
	**/
	function fetch( $key ) 
	{
		$cachefile	= $this->_getCachedFile($key);
		if (!file_exists($cachefile)) {
			return false;
		}
		
		if ( !($fp = fopen($cachefile, "r")) ) {
			return false;
		}
		
		// get shared lock
		flock($fp, LOCK_SH);
		
		$data	= file_get_contents($cachefile);
		fclose($fp);
		
		$data	= @unserialize($data);
		// empty data or expired?
		if (!$data || time() > $data[0]) {
			@unlink($cachefile);
			return false;
		}
		
		return $data[1];
	}
 
	/**
	Store cache
		@param $key string
		@param $data mixed
		@public
	**/
	function store( $key, $data ) 
	{
		// open in read/write mode
		$cachefile	= $this->_getCachedFile($key);
		if ( !($fp = fopen($cachefile, "a+")) ) {
			throw new Exception('Could not write to cache.');
		}
		
		flock($fp, LOCK_EX);	// set exclusive lock
		fseek($fp, 0);
		
		// initialize file
		ftruncate($fp, 0);
		
		// serialize
		$data	= serialize(array(time() + $this->_ttl, $data));
		if (fwrite($fp, $data) === false) {
			throw new Exception('Could not write to cache.');
		}
		
		fclose($fp);
	}
 
	/**
	Delete cache
		@param $key string
		@public
	**/
	function delete( $key ) 
	{
		$cachefile = $this->_getCachedFile($key);
		
		return @unlink($cachefile);
	}
 
	/**
	Get stored cache in file
		@param $key string
		@private
	**/
	private function _getCachedFile( $key ) 
	{
		$cachefile	= BASE_PATH .DS. '_cache' .DS. md5($key);
		
		return $cachefile;
	}
}
