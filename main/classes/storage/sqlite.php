<?php
/*
* $Id: sqlite.php, version 1.0
*
*/

defined('_PRIVATE') or die('Direct access not allowed');

class sqlite_storage extends storage
{
	private $_resource	= null;
	private $_cursor	= null;
	
	/**
	Class constructor
		@public
	**/
	function __construct() 
	{
		parent::__construct();
	}
	
	/**
	Open SQLite resource
		@param $url string
		@public
	**/
	function connect( $dsn ) 
	{
		$url	= parse_url($dsn);
		
		// get db
		@list($db, $this->_table_prefix)	= explode(':', substr($url['path'], 1) );
		
		// get path
		$db_path	= $url['host'];
		if ($db_path == 'localhost') {
			$db_path	= BASE_PATH;
		}
		$db_path	= $db_path . $db .'.db.';

		$this->_resource	= sqlite_open($db_path, 0666, $error);
		if (!$this->_resource) {
			if ($this->_debug) {
				trigger_error($error(), E_USER_ERROR);
			}
			
			return false;
		}
		
		return true;
	}

	/**
	Issue an SQL statement
		@param $sql string
		@param $offset integer
		@param $limit integer
		@public
	**/
	function query( $sql, $offset=0, $limit=0 ) 
	{
		if ($limit > 0 && $offset == 0) {
			$sql	.= "\nLIMIT {$limit}";
		} else if ($limit > 0 || $offset > 0) {
			$sql	.= "\nLIMIT {$offset}, {$limit}";
		}
		
		// replace table prefix
		$sql	= str_replace('{TABLE_PREFIX}', $this->_table_prefix, $sql);
		
		$this->_cursor	= sqlite_query($this->_resource, $sql);

		if (sqlite_last_error($this->_resource) && $this->_debug) {
			trigger_error($this->error(), E_USER_ERROR);
		}
		
		return $this->_cursor;
	}

	/**
	Fetch resultset as an object
		@public
	**/
	function fetch_object() 
	{
		$result	= null;
		
		if ($this->_cursor) {
			$result	= sqlite_fetch_object($this->_cursor);
		}
		
		sqlite_close($this->_resource);
		
		return $result;
	}

	/**
	Fetch a list of database objects
		@param $key string
		@public
	**/
	function fetch_object_list( $key='' ) 
	{
		if (!$this->_cursor) {
			return null;
		}
		
		$array	= array();
		while ($row = sqlite_fetch_object($this->_cursor)) {
			if ($key) {
				$array[$row->$key]	= $row;
			} else {
				$array[]			= $row;
			}
		}
		
		sqlite_close($this->_resource);
		
		return $array;
	}
	
	/**
	Fetch resultset as an array
		@public
	**/
	function fetch_array() 
	{
		if ($this->_cursor) {
			return sqlite_fetch_array($this->_cursor, SQLITE_ASSOC);
		}
	}

	/**
	Returns number of rows returned from the last query
		@public
	**/
	function record_count() 
	{
		if ($this->_cursor) {
			return sqlite_num_rows($this->_cursor);
		}
	}

	/**
	Return an individual result field from the last query
		@param $row integer
		@public
	**/
	function result( $row=0 ) 
	{
		if ($this->_cursor && sqlite_num_rows($this->_cursor) > $row) {
			$res	= sqlite_fetch_single($this->_cursor, $row);
		
			sqlite_close($this->_resource);
			
			return $res;
		}
	}

	/**
	Return an individual result field from the last query as an array
		@param $index integer
		@public
	**/
	function result_array( $index=0 ) 
	{
		if ($this->_cursor) {
			$array	= array();
			
			while ($row = sqlite_array_query($this->_cursor)) {
				$array[] = $row[$index];
			}
			
			return $array;
		}
		
		sqlite_close($this->_resource);
	}

	/**
	Get last query error
		@public
	**/
	function error() 
	{
		$err_no		= sqlite_last_error($this->_resource);
		$err_msg	= sqlite_error_string($err_no);
		
		if ($err_no) {
			return 'Error ' .$err_no .': '. $err_msg;
		}
		
		return false;
	}

	/**
	Determine the number of rows changed by the last query
		@public
	**/
	function affected_rows() 
	{
		return sqlite_changes($this->_resource);
	}

	/**
	Get last insert id
		@public
	**/
	function last_insert_id() 
	{
		return sqlite_last_insert_rowid($this->_resource);
	}

	/**
	Get a database escaped string
		@param $text string
		@public
	**/
	function getEscaped( $text )
	{
		return sqlite_escape_string($text);
	}

	/**
	Get current database date
		@public
	**/
	function curdate( $unix_ts=false ) 
	{
		if( $unix_ts ) {
			$sql	= "SELECT strftime('%s', 'now')";
		}
		else {
			$sql	= "SELECT DATETIME('now')";
		}
		$this->query( $sql );
		
		return $this->result();
	}
	
	/** 
	* Helper functions
	*/
	// Create an inline user-defined functions
	// to support other RDBMS functions/procedures
	private function _loadUDF()
	{
		// md5
		sqlite_create_function($this->_resource, 'md5', 'md5', 1);
		// sha1
		sqlite_create_function($this->_resource, 'sha1', 'sha1', 1);
	}
}
