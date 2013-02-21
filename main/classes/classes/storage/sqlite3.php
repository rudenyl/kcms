<?php
/*
* $Id: sqlite3.php, version 1.0
* SQLite3-based storage class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

// load UDF helper file
include_once( dirname(__FILE__) .DS. 'sqlite.udf.php' );

// sqlite3s class
class sqlite3_storage extends storage
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
		@param $dsn string
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
		$db_path	= $db_path .DS. $db .'.db';

		$this->_resource	= new SQLite3($db_path);
		if ($this->_resource) {
			// load UDF
			$this->_loadUDF();
		}
		else {
			if ($this->_debug) {
				trigger_error($this->error(), E_USER_ERROR);
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
			$sql	.= "\n LIMIT {$limit}";
		} else if ($limit > 0 || $offset > 0) {
			$sql	.= "\n LIMIT {$offset}, {$limit}";
		}
		
		$sql	= str_replace('{TABLE_PREFIX}', $this->_table_prefix, $sql);
		// do some fixes
		$this->_translate($sql);
		
		$this->_cursor	= $this->_resource->query($sql);

		if ($this->_resource->lastErrorCode() && $this->_debug) {
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
		
		while ($row = $this->_cursor->fetchArray(SQLITE3_ASSOC)) {
			$result	= $this->toObject($row);
		}
		
		//$this->_resource->close();
		
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
		while ($row = $this->_cursor->fetchArray()) {
			$row	= $this->toObject($row);
			
			if ($key) {
				$array[$row->$key]	= $row;
			} else {
				$array[]			= $row;
			}
		}
		
		//$this->_resource->close();
		
		return $array;
	}
	
	/**
	Fetch resultset as an array
		@public
	**/
	function fetch_array() 
	{
		if ($this->_cursor) {
			return $this->_cursor->fetchArray(SQLITE3_ASSOC);
		}
	}

	/**
	Returns number of rows returned from the last query
		@public
	**/
	function record_count() 
	{
		if ($this->_cursor) {
			return $this->_cursor->numColumns();
		}
	}

	/**
	Return an individual result field from the last query
		@param $row integer
		@public
	**/
	function result( $row=0 ) 
	{
		if ($this->_cursor && $this->_cursor->numColumns() > $row) {
			$res	= $this->_cursor->fetchArray();
			if ($res) {
				$res	= $res{$row};
			}
		
			//$this->_resource->close();
			
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
			
			while ($row = $this->_cursor->fetchArray()) {
				$array[] = $row[$index];
			}
			
			return $array;
		}
		
		//$this->_resource->close();
	}

	/**
	Get last query error
		@public
	**/
	function error() 
	{
		$err_no		= $this->_resource->lastErrorCode();
		$err_msg	= $this->_resource->lastErrorMsg();
		
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
		return $this->_resource->changes();
	}

	/**
	Get last insert id
		@public
	**/
	function last_insert_id() 
	{
		return $this->_resource->lastInsertRowID();
	}

	/**
	Get table columns
		@param $table_name string
		@public
	**/
	function get_table_columns( $table_name ) 
	{
		$this->query( "PRAGMA table_info({$table_name})" );
		
		/**/
		// PRAGMA tabl_info returns one additional columns for the column id (cid)
		// show set array index to 1
		return $this->result_array(1);
	}

	/**
	Get a database escaped string
		@param $text string
		@public
	**/
	function getEscaped( $text )
	{
		return $this->_resource->escapeString($text);
	}

	/**
	Get current database date
		@param $unix_ts boolean
		@public
	**/
	function curdate( $unix_ts=false ) 
	{
		if ($unix_ts) {
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
		/* encryptions */
		// md5
		$this->_resource->createFunction('md5', 'md5', 1);
		// sha1
		$this->_resource->createFunction('sha1', 'sha1', 1);
		
		/* string functions */
		// CONCAT
		$this->_resource->createFunction('CONCAT', 'sqlite__CONCAT');
		// SUBSTRING_INDEX
		$this->_resource->createFunction('SUBSTRING_INDEX', 'sqlite__SUBSTRING_INDEX', 3);
		
		/* conditional statements */
		// IF
		$this->_resource->createFunction('IF', 'sqlite__IF', 3);
		// IFNULL
		$this->_resource->createFunction('IFNULL', 'sqlite__IFNULL');
		
		/* dates */
		// DATEDIFF
		$this->_resource->createFunction('DATEDIFF', 'sqlite__DATEDIFF', 2);
		// YEAR
		$this->_resource->createFunction('YEAR', 'sqlite__YEAR', 1);
		// MONTH
		$this->_resource->createFunction('MONTH', 'sqlite__MONTH', 1);
		// DAY
		$this->_resource->createFunction('DAY', 'sqlite__DAY', 1);
		// NOW
		$this->_resource->createFunction('NOW', 'sqlite__NOW');
		// DATE_FORMAT
		$this->_resource->createFunction('DATE_FORMAT', 'sqlite__DATE_FORMAT', 2);
		// LAST_DAY
		$this->_resource->createFunction('LAST_DAY', 'sqlite__LAST_DAY', 1);
	}
	
	// translate non-sqlite sql statements to sqlite
	private function _translate( &$sql )
	{
		$sql	= str_replace('IGNORE', 'OR IGNORE', $sql);
		$sql	= str_replace('TRUNCATE', 'DELETE FROM', $sql);
		$sql	= str_replace('NOW()', "strftime('%Y-%m-%d', 'now')", $sql);
	}
} 
