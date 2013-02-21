<?php
/*
* $Id: session_abstract.class.php
* Session handling db implementation
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

class SessionDBHandler extends Session_Abstract
{
    private $__maxlife;
    private $__db;
	
	/**
	Constructor
		@abstract
	**/
	function __construct()
	{
		// init db
		$this->__db	=& Factory::getDBO();
	}
	
	/**
	Initialize object
		@abstract
	**/
	function init()
	{
		$table_name	= $this->__db->getTablePrefix() .'_session';
	
		// check if table is present
		if ($this->__db->table_exists($table_name)) {
			// map handlers
			session_set_save_handler(
				array(&$this, 'open'),
				array(&$this, 'close'),
				array(&$this, 'read'),
				array(&$this, 'write'),
				array(&$this, 'destroy'),
				array(&$this, 'gc')
			);
			
			// the following prevents unexpected effects when using objects as save handlers
			register_shutdown_function((array(&$this, 'close')));
		}
	}
	
	/**
	Load session
		@param $save_path string
		@param $sess_name string
		@abstract
	**/
    function open( $save_path, $sess_name ) 
	{ 
		// get session-lifetime 
		$this->__maxlife	= get_cfg_var("session.gc_maxlifetime"); 

        return true;
    }
	
	/**
	Close the session
		@abstract
	**/
    function close() 
	{
		// call garbage collector
        $this->gc($this->__maxlife);
		
        return true;
    }
	
	/**
	Fetch session data
		@param $sess_id string
		@abstract
	**/
    function read( $sess_id )
	{
		$ts		= time();
		$result	= '';
		
        // fetch session data
		$sql	= "SELECT `sess_data`"
		."\n FROM {TABLE_PREFIX}_session"
		."\n WHERE `sess_id` = " . $this->__db->Quote($sess_id)
		."\n AND `sess_expires` > " . $this->__db->Quote($ts)
		;
		$this->__db->query($sql);
		
		if ($row = $this->__db->fetch_object()) {
			$result	= $row->sess_data;
		}
		
        return $result; 
    }
	
	/**
	Save session data
		@param $save_path string
		@param $sess_name string
		@abstract
	**/
    function write( $sess_id, $sess_data )
	{
		$auth	=& Factory::getAuth();
		
        // update expiry
        $expiry	= time() + $this->__maxlife; 
		
        // check if exists
		$sql	= "SELECT count(*) as found"
		."\n FROM {TABLE_PREFIX}_session"
		."\n WHERE `sess_id` = " . $this->__db->Quote($sess_id)
		;
		$this->__db->query($sql);
		$found	= (int)$this->__db->result();
		
		if ($found) {
			// update
			$sql	= "UPDATE {TABLE_PREFIX}_session"
			."\n SET `sess_expires` = " . $this->__db->Quote($expiry)
			."\n ,`user_id` = " . $this->__db->Quote($auth->id)
			."\n ,`sess_data` = " . $this->__db->Quote($sess_data)
			."\n WHERE `sess_id` = " . $this->__db->Quote($sess_id)
			;
		}
		else {
			$data	= array(
				'sess_expires' => $expiry,
				'user_id' => $auth->id,
				'sess_data' => $sess_data,
				'sess_id' => $sess_id
			);
			$values	= array();
			foreach ($data as $k=>&$v) {
				$k	= $this->__db->NameQuote($k);
				
				$values[$k]	= $this->__db->Quote($v);
			}
			
			$keys	= implode(',', array_keys($values));
			$values	= implode(',', array_values($values));
			
			// add
			$sql	= "INSERT INTO {TABLE_PREFIX}_session({$keys})"
			."\n VALUES({$values})"
			;
		}
		
		// commit
		$this->__db->query($sql);
		
		// get affected rows
		$result	= $this->__db->affected_rows();
		
        return $result; 
    }
	
	/**
	Destroy the session
		@param $sess_id string
		@abstract
	**/
    function destroy( $sess_id )
	{ 
		// delete session
		$sql	= "DELETE FROM {TABLE_PREFIX}_session"
		."\n WHERE `sess_id` = " . $this->__db->Quote($sess_id)
		;
		$this->__db->query($sql);
		
		// get affected rows
		$result	= $this->__db->affected_rows();
		
        return $result; 
    }
	
	/**
	Garbage collection
		@param $maxlife integer
		@abstract
	**/
    function gc( $maxlife )
	{
		// get timestamp
        $ts	= time(); 
	
		// remove old sessions
		$sql	= "DELETE FROM {TABLE_PREFIX}_session"
		."\n WHERE `sess_expires` + {$maxlife} < " . $this->__db->Quote($ts)
		;
		$this->__db->query($sql);
		
		// get affected rows
		$result	= $this->__db->affected_rows();
		
        return $result; 
    }
} 
