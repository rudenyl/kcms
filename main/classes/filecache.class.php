<?php
/*
* $Id: filecache.class.php
*
* Caching system using filesystem
*/

defined('_PRIVATE') or die('Direct access not allowed');

class FileCache extends Cache_Abstract
{
	protected $_ttl	= 60;

	function __construct( $ttl=1 )
	{
		// set cache TTL
		if ((int)$ttl < 1) {
			$ttl	= 1;
		}
		
		$this->_ttl	= $ttl;
	}
	
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
 
	function delete( $key ) 
	{
		$cachefile = $this->_getCachedFile($key);
		
		return @unlink($cachefile);
	}
 
	private function _getCachedFile( $key ) 
	{
		$cachefile	= BASE_PATH .DS. '_cache' .DS. '_pages' .DS. md5($key);
		
		return $cachefile;
	}
}
