<?php
/*
* $Id: storage.class.php, version 1.0
* Storage base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

class storage 
{
	protected $_debug			= 0;	
	protected $_table_prefix	= '';
	protected $_timezone		= '';
	
	/**
	Class constructor
		@public
	**/
	function __construct() 
	{
	}
	
	/**
	Load storage preference
		@param $type string
		@public
	**/
	function load( $dsn='', $type=null )
	{
		$dbo	= null;
	
		// parse dsn
		$url	= parse_url($dsn);
		// sniff type from scheme
		if ($type === null) {
			@list($type, $_port)	= explode(':', $url['scheme']);
		}

		$storage_path	= PATH_CLASSES .DS. 'storage' .DS. $type .'.php';
		if (!is_file($storage_path)) {
			if ($this->_debug) {
				trigger_error("Storage type \"{$type}\" not found. Reverting to default.", E_USER_NOTICE);
			}
		
			// fallback to default (mysql)
			$type			= 'mysql';
			$storage_path	= PATH_CLASSES .DS. 'storage' .DS. $type .'.php';
		}
		
		// load class file
		require_once( $storage_path );
		
		$class_inst	= "{$type}_storage";
		
		if (class_exists($class_inst, false)) {
			$dbo	= new $class_inst();
			
			// attemp to connect to db
			$dbo->connect($dsn);
		}
		
		return $dbo;
	}
	
	/**
	Set timezone setting
		@param $tz string
		@public
	**/
	public function set_timezone( $tz )
	{
		$this->_timezone	= $tz;
	}
	
	/**
	Initialize a database connection
		@param $url string
		@public
		
		ex:	mysql://username:password@host/db
	**/
	public function connect( $url ) {}
	/**
	Issue an SQL statement
		@param $sql string
		@param $offset integer
		@param $limit integer
		@public
	**/
	public function query( $sql, $offset=0, $limit=0 ) {}
	/**
	Fetch resultset as an object
		@public
	**/
	public function fetch_object() {}
	/**
	Fetch a list of database objects
		@param $key string
		@public
	**/
	public function fetch_object_list( $key='' ) {}
	/**
	Fetch resultset as an array
		@public
	**/
	public function fetch_array() {}
	/**
	Returns number of rows returned from the last query
		@public
	**/
	public function record_count() {}
	/**
	Return an individual result field from the last query
		@param $row integer
		@public
	**/
	public function result( $row=0 ) {}
	/**
	Return an individual result field from the last query as an array
		@param $index integer
		@public
	**/
	public function result_array( $index=0 ) {}
	/**
	Get last insert id
		@public
	**/
	public function last_insert_id() {}
	/**
	Get table columns
		@param $table_name string
		@public
	**/
	public function get_table_columns( $table_name ) {}
	/**
	Get last query statement
		@public
	**/
	public function last_query() {}
	/**
	Check if table exists
		@param $table_name string
		@public
	**/
	public function table_exists( $table_name ) {}
	/**
	Get last query error
		@public
	**/
	public function error() {}
	/**
	Determine the number of rows changed by the last query
		@public
	**/
	public function affected_rows() {}
	/**
	Get a database escaped string
		@param $text string
		@public
	**/
	public function getEscaped( $text ) {}
	/**
	Get current database date
		@param $unix_ts boolean
		@public
	**/
	public function curdate( $unix_ts=false ) {}
	
	/**
	* Get a quoted database escaped string
		@param $text string
		@param $escaped boolean
		@public
	*/
	function Quote( $text, $escaped=true )
	{
		return '\'' .($escaped ? $this->getEscaped($text) : $text). '\'';
	}
	
	/**
	* Quote an identifier name
		@param $name string
		@param $quote_text string
		@public
	*/
	function nameQuote( $name, $quote_text='`' )
	{
		$quoted_name	= $name;
		
		// Only quote if the name is not using dot-notation
		if ( strpos($name, '.' ) === false) {
			if (strlen( $quote_text ) == 1) {
				$quoted_name	= $quote_text . $name . $quote_text;
			} else {
				$quoted_name	= $quote_text{0} . $name . $quote_text{1};
			}
		}
		
		return $quoted_name;
	}

	/**
	Add row into table
		@param $table_name string
		@param $row object
		@param $key_field string
		@param $convert_array_to_string boolean
		@public
	**/
	function add_row( $table_name, &$row, $key_field=null, $convert_array_to_string=false ) 
	{
		$query	= "INSERT INTO {$table_name}(%s)"
		."\n VALUES(%s)"
		;
		
		$data	= array();
		foreach (get_object_vars($row) as $k=>$v) {
			if ($v === null) {
				continue;
			}
			if (is_array($v) || is_object($v)) {
				// convert to readable data
				if ($convert_array_to_string) {
					$v	= json_encode($v);
				}
				else {
					continue;
				}
			}
			
			$key		= $this->NameQuote($k);
			$data[$key]	= $this->Quote($v);
		}
		
		$sql	= sprintf($query, implode(',', array_keys($data)), implode(',', array_values($data)));
		if (!$this->query($sql)) {
			return false;
		}
		
		// set last insert id
		$id	= $this->last_insert_id();
		if ($key_field && $id) {
			$row->{$key_field} = $id;
		}
		
		return true;
	}
	
	/**
	Update a row
		@param $table_name string
		@param $row object
		@param $key_field string
		@param $convert_array_to_string boolean
		@public
	**/
	function update_row( $table_name, &$row, $key_field, $convert_array_to_string=false ) 
	{
		$query	= "UPDATE {$table_name}"
		."\n SET %s"
		."\n WHERE %s"
		;
		
		$data	= array();
		foreach (get_object_vars($row) as $k=>$v) {
			// convert to readable data
			if (is_array($v) || is_object($v)) {
				// convert to readable data
				if ($convert_array_to_string) {
					$v	= json_encode($v);
				}
				else {
					continue;
				}
			}
			
			// get primary key
			if ($k == $key_field) {
				$where	= $key_field .'='. $this->Quote($v);
				continue;
			}
			
			if ($v === null) {
				continue;
			}
			
			$v		= ($v == '') ? "''" : $this->Quote($v);
			$data[] = $this->NameQuote($k) .'='. $v;
		}
		
		$sql	= sprintf($query, implode(',', $data), $where);
		$result	= $this->query($sql);
		
		return $result;
	}
	
	/**
	Bind array to a row object
		@param $array array
		@param $row object
		@public
	**/
	function bindArrayToRow( $array, &$row ) 
	{
		if (!is_array($array) || !is_object($row)) {
			return false;
		}

		foreach (get_object_vars($row) as $k=>$v) {
			if (isset($array[$k])) {
				// always check slashes
				$row->{$k}	= (get_magic_quotes_gpc()) ? stripslashes($array[$k]) : $array[$k];
			}
		}

		return true;
	}
	
	/**
	Get table prefix
		@public
	*/
	function getTablePrefix()
	{
		return $this->_table_prefix;
	}
	
	/**
	array to a row object
		@param $array array
		@public
	**/
	function toObject( $array )
	{
		$object 	= new stdclass();
		
		if ($array) {
			foreach ($array as $k=>$v) {
				$object->{$k}	= $v;
			}
		}
		
		return $object;
	}
} // storage class
