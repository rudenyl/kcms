<?php
/*
* $Id: mysql.php, version 1.0
* MySQL-based storage class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

class mysql_storage extends storage
{
	private $_resource	= null;
	private $_cursor	= null;
	private $_query		= '';
	
	/**
	Class constructor
		@public
	**/
	function __construct() 
	{
		parent::__construct();
	}
	
	/**
	Initialize a database connection
		@param $dsn string
		@public
		
		ex:	mysql://username:password@host/db
	**/
	function connect( $dsn ) 
	{
		$url	= parse_url($dsn);
		
		// Allow for non-standard MySQL port
		if ( isset($url['port']) ) {
			$url['host']	= $url['host'] .':'. $url['port'];
		}

		$this->_resource	= mysql_connect($url['host'], $url['user'], $url['pass']);
		if (!$this->_resource) {
			if ($this->_debug) {
				trigger_error(mysql_error(), E_USER_ERROR);
			}
			
			return false;
		}
		
		@list($db, $this->_table_prefix)	= explode(':', substr($url['path'], 1) );
		if (!mysql_select_db($db)) {
			if ($this->_debug) {
				trigger_error(mysql_error(), E_USER_ERROR);
			}
			
			return false;
		}
		
		// set encoding
		// force UTF-8
		$this->query( "SET NAMES 'utf8'" );
		// set timezone
		if ($this->_timezone) {
			$this->query( "SET time_zone = '{$this->_timezone}'" );
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
		
		// replace table prefix
		$sql	= str_replace('{TABLE_PREFIX}', $this->_table_prefix, $sql);
		
		$this->_cursor	= mysql_query($sql, $this->_resource);

		if (mysql_errno() && $this->_debug) {
			trigger_error(mysql_error() ."\nQuery: ". htmlspecialchars($sql), E_USER_ERROR );
		}
		
		return $this->_cursor;
	}

	/**
	Fetch resultset as an object
		@public
	**/
	function fetch_object() 
	{
		if ($this->_cursor) {
			return @mysql_fetch_object($this->_cursor);
		}
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
		while ($row = $this->fetch_object()) {
			if ($key) {
				$array[$row->$key]	= $row;
			} else {
				$array[]			= $row;
			}
		}
		return $array;
	}
	
	/**
	Fetch resultset as an array
		@public
	**/
	function fetch_array() 
	{
		if ($this->_cursor) {
			return mysql_fetch_array($this->_cursor, MYSQL_ASSOC);
		}
	}

	/**
	Returns number of rows returned from the last query
		@public
	**/
	function record_count() 
	{
		if ($this->_cursor) {
			return mysql_num_rows($this->_cursor);
		}
	}

	/**
	Return an individual result field from the last query
		@param $row integer
		@public
	**/
	function result( $row=0 ) 
	{
		if ($this->_cursor && mysql_num_rows($this->_cursor) > $row) {
			$res	= mysql_result($this->_cursor, $row);
			mysql_free_result($this->_cursor);
			
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
			
			while ($row = mysql_fetch_row($this->_cursor)) {
				$array[] = $row[$index];
			}
			
			mysql_free_result($this->_cursor);
			
			return $array;
		}
	}

	/**
	Get last query error
		@public
	**/
	function error() 
	{
		$err_no		= mysql_errno();
		$err_msg	= mysql_error();
		
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
		return mysql_affected_rows($this->_resource);
	}

	/**
	Get last insert id
		@public
	**/
	function last_insert_id() 
	{
		return mysql_insert_id($this->_resource);
	}

	/**
	Get last query
		@public
	**/
	function last_query()
	{
		return $this->_query;
	}

	/**
	Get table columns
		@param $table_name string
		@public
	**/
	function get_table_columns( $table_name ) 
	{
		$this->query( "SHOW COLUMNS FROM {$table_name}" );
		return $this->result_array();
	}

	/**
	Check if table exists
		@param $table_name string
		@public
	**/
	function table_exists( $table_name )
	{
		$this->query("SHOW tables LIKE '%{$table_name}%'");
		
		$tables	= $this->result_array();
		$n		= count($tables);
		
		return $n;
	}
	
	/**
	Get a database escaped string
		@param $text string
		@public
	**/
	function getEscaped( $text )
	{
		return mysql_real_escape_string( $text, $this->_resource );
	}

	/**
	Get current database date
		@param $unix_ts boolean
		@public
	**/
	function curdate( $unix_ts=false ) 
	{
		if( $unix_ts ) {
			$sql	= "SELECT UNIX_TIMESTAMP( now() )";
		}
		else {
			$sql	= "SELECT now()";
		}
		$this->query( $sql );
		
		return $this->result();
	}
} // database class
