<?php
/*
* $Id: parameter.class.php
* Key-value pairing manipulation class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class Parameter
{
	var $_raw				= null;
	var $_object			= null;
	var $_array				= array();
	
	protected $terminator	= null;
	protected $separator	= "=";
	protected $_modified	= false;
	
	/**
	Class constructor
		@public
	**/
	function __construct( $string, $terminator=null, $separator=null )
	{
		// set raw string
		$this->_raw		= $string;
		
		// create parameters object
		$this->_object	= new stdclass();
		
		// set term char
		if ($terminator) {
			$this->terminator	= $terminator;
		}
		// set separator char
		if ($separator) {
			$this->separator	= $separator;
		}
		
		// format string
		$this->_getFormatted();
	}
	
	/**
	Get key value
		@param $key string
		@param $default mixed
		@public
	**/
	public function get( $key, $default=null )
	{
		if (isset($this->_object->$key)) {
			return ($this->_object->$key ? $this->_object->$key : $default);
		}
		else {
			return $default;
		}
	}
	
	/**
	Set key value
		@param $key string
		@param $value mixed
		@public
	**/
	public function set( $key, $value=null )
	{
		// set object
		$this->_object->$key	= $value;
		// set array
		$this->_array[$key]		= $value;
		
		// yeah, its modified
		$this->_modified		= true;
	}
	
	/**
	Convert key-pair array to string
		@public
	**/
	public function toString()
	{
		$data	= '';
		
		if (!$this->_modified) {
			$data	= $this->_raw;
		}
		else {
			if ($this->_array) {
				$list	= array();
				foreach ($this->_array as $k=>$v) {
					$list[]	= $k . $this->separator . $v;
				}
				
				$data	= implode($this->terminator, $list);
			}
		}
		
		return $data;
	}
	
	/**
	Format string to key-pair value
		@private
	**/
	private function _getFormatted()
	{
		if (empty($this->_raw)) {
			return array();
		}
		
		/**/
		if ($this->terminator === null) {
			$this->terminator	= '<br />';
			
			$this->_raw			= nl2br($this->_raw);
			$this->_raw			= str_replace('\n', $this->terminator, $this->_raw);
		}
		
		// terminated by line-feed
		$arr	= explode($this->terminator, $this->_raw);
		
		// walk
		foreach ($arr as $s) {
			$s_pos	= strpos($s, $this->separator);
			
			if ($s_pos !== false) {
				$k	= substr($s, 0, $s_pos);
				$k	= trim($k);
				$v	= substr($s, $s_pos + 1, strlen($s) - $s_pos);
				
				if (empty($k)) {
					continue;
				}
				
				// add to object
				$this->_object->$k	= $v;
				// add to array
				$this->_array[$k]	= $v;
			}
		}
	}
}
