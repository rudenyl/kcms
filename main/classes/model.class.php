<?php
/*
* $Id: model.class.php, version 0.1.172011
* Model base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

class Model
{
	var	$limit				= 0;
	var $limitstart			= 0;

	protected $_db			= null;
	protected $__tbl_key	= '';
	protected $__tbl_name	= '';
	protected $__tbl_alias	= '';
	protected $___vars		= null;

	/**
	Class constructor
		@public
	**/
	function __construct()
	{
		$this->_db	=& Factory::getDBO();
		
		if( $args = func_get_args() ) {
			$table_key	= $args{0};
			$table_name	= $args{1};
			
			$this->__initTable($table_key, $table_name);
		}
	}
	
	/**
	Get total rows
		@param $sql string
		@public
	**/
	public function getTotal( $sql='' )
	{
		$where		= $this->getListWhere();
		
		if( empty($sql) ) {
			$sql	= "SELECT count(*)"
			."\n FROM {$this->__tbl_name} {$this->__tbl_alias}"
			;
		}
		$sql	.= ($where ? "\n" .$where : '');
		
		$this->_parseSQL($sql);
		$this->_db->query($sql);
		
		return (int)$this->_db->result();
	}
	
	/**
	Get query results in array of objects
		@param $sql string
		@return object array
		@public
	**/
	public function &getList( $sql='' )
	{
		$where		= $this->getListWhere();
		$order_by	= $this->getOrderBy();
		
		if( empty($sql) ) {
			$sql	= "SELECT *"
			."\n FROM {$this->__tbl_name} {$this->__tbl_alias}"
			;
		}
		$sql	.= ($where ? "\n" .$where : '');
		$sql	.= ($order_by ? "\n" .$order_by : '');
		
		$this->_parseSQL($sql);
		
		$this->_db->query($sql, $this->limitstart, $this->limit);
		$rows = $this->_db->fetch_object_list();
		
		return $rows;
	}
	
	/**
	Get a single row
		@param $id int
		@param $sql string
		@return object
		@public
	**/
	public function &getRow( $id, $sql=null )
	{
		$id		= intval($id);
	
		if (empty($sql)) {
			$sql	= "SELECT *"
			."\n FROM {$this->__tbl_name}"
			."\n WHERE `{$this->__tbl_key}` = " .$this->_db->Quote($id)
			;
		}
		$this->_parseSQL($sql);
		$this->_db->query($sql);
		
		$rows = $this->_db->fetch_object_list();
		if( $rows ) {
			return $rows[0];
		}
		else {
			// reset key
			$this->___vars->{$this->__tbl_key}	= 0;
			
			return $this->___vars;
		}
	}
	
	/**
	Get related model relative to path
		@param $name string
		@return object
		@public
	**/
	public function &getModel( $name='' )
	{
		$model		= $this;	// self

		// get app name
		$appName	= '';
		if ( preg_match( '/(.*?)Model(.*)/i', get_class($this), $match) ) {
			$appName	= strtolower( $match[1] );
		}
		
		$modelPath	= PATH_APPLICATIONS .DS. $appName .DS. 'models' .DS. $name.'.php';
		
		// load model
		if( file_exists($modelPath) && is_file($modelPath) ) {
			require_once( $modelPath );
			
			$this_class_name	= get_class($this);
			
			$modelClassName	= ucfirst($appName) .'Model'. ucfirst($name);
			$model			= new $modelClassName();
				
			if( !is_subclass_of($model, 'Model')  ) {
				return false;
			}
		}
		
		return $model;
	}
	
	/**
	Reorder list
		@param $where string
		@public
	**/
	function reorder( $where='' )
	{
		// get ordering tag
		$order_tag	= 'ordering';
		
		// get table vars
		$vars		= $this->getObjectVars();

		$order_tag_set	= false;
		if( $vars ) {
			foreach($vars as $k=>$v) {
				if( $k == $order_tag ) {
					$order_tag_set	= true;
					break;
				}
			}
		}
		if( !$order_tag_set ) {
			return false;	// not supported
		}

		// get item ordering
		$sql	= "SELECT {$this->__tbl_key},{$order_tag}"
		."\n FROM {$this->__tbl_name}"
		."\n WHERE {$order_tag} >= 0"
		. ( $where ? "\n AND " . $where : '' )
		."\n ORDER BY {$order_tag}"
		;
		$this->_db->query($sql);
		
		$orders	= $this->_db->fetch_object_list();
		
		if( $orders ) {
			// get key
			$k	= $this->__tbl_key;
			
			for($i=0, $n=count($orders); $i < $n; $i++) {
				if( $orders[$i]->{$order_tag} < 1) {
					continue;
				}
				
				$order_level	= $orders[$i]->{$order_tag};
				if ($order_level != $i+1) {
					$order_level	= $i + 1;
					
					$sql	= "UPDATE {$this->__tbl_name}"
					."\n SET {$order_tag} = ". $this->_db->Quote($order_level)
					."\n WHERE {$this->__tbl_key} = " . $this->_db->Quote($orders[$i]->$k)
					;
					$this->_db->query($sql);
				}
			}
			
			return true;
		}
		
		return false;
	}

	/**
	Get WHERE statement piece
		@public
	**/
	public function getListWhere() 
	{
		return '';
	}
	
	/**
	Get ORDER BY statement piece
		@public
	**/
	public function getOrderBy() 
	{
		return '';
	}
	
	/**
	Bind values to table columns
		@param $vars mixed
		@public
	**/
	public function bind( $vars )
	{
		// re-init variables
		$this->_initVars();
		
		// convert to array if type object
		$vars	= $this->toArray($vars);
	
		return $this->_db->bindArrayToRow($vars, $this->___vars);
	}
	
	/**
	Delete row
		@param $id int
		@public
	**/
	public function delete( $id )
	{
		$id		= intval($id);
	
		$sql	= "DELETE"
		."\n FROM {$this->__tbl_name}"
		."\n WHERE `{$this->__tbl_key}` = " .$this->_db->Quote($id)
		;
		$this->_db->query($sql);
		
		return ($this->_db->affected_rows() > 0);
	}
	
	/**
	Save row to table
		@param $row object
		@public
	**/
	public function store( $row=null, $auto=true ) 
	{
		// get vars
		if ($row === null || !is_object($row)) {
			$row	= $this->___vars;
		}
		
		$__kv		= $row->{$this->__tbl_key};

		if ($__kv && $auto) {
			$result = $this->_db->update_row($this->__tbl_name, $row, $this->__tbl_key);
		} 
		else {
			$result = $this->_db->add_row($this->__tbl_name, $row, $this->__tbl_key);
		}

		return array(
			'cursor' => $result,
			'row' => $row,
			'error' => $this->_db->error()
		);
	}
	
	/**
	Assign sql statement variables values
		@public
	**/
	public function prepare()
	{
		if ($n_args = func_num_args()) {
			$args	= func_get_args();
			
			$sql	= $args[0];
			array_shift($args);
			
			// get fields
			preg_match_all('/:(\w)+/', $sql, $matches);
			
			if (count(@$matches[0]) < count($args)) {
				return false;
			}
			
			// parse
			foreach ($matches[0] as $i=>$match) {
				$value	= $this->_db->Quote($args[$i]);
				$sql	= str_replace($match, $value, $sql);
			}
			
			return $sql;
		}
		
		return '';
	}
	
	/**
	Get object vars
		@public
	**/
	public function getObjectVars()
	{
		return $this->___vars;
	}
	
	/**
	Get last sql query error
		@public
	**/
	public function getLastError()
	{
		return $this->_db->error();
	}
	
	/**
	Convert object to array
		@param $data object
		@public
	**/
	public function toArray( $data )
	{
		$array	= array();
		if (is_object($data)) {
			foreach( get_object_vars($data) as $k=>$v ) {
				$array[$k]	= $v;
			}
		}
		else {
			$array	= $data;
		}
		
		return $array;
	}
	
	/**
	Init table column references
		@protected
	**/
	protected function _initVars()
	{
		// initialize
		$this->___vars	= new stdclass();
	
		// get table columns
		$fields = $this->_db->get_table_columns($this->__tbl_name);
		if ($fields) {
			foreach($fields as $f) {
				$this->___vars->{$f} = null;
			}
		}
	}
	
	/**
	Initialize class references
		@param $table_key string
		@param $table_name string
		@private
	**/
	private function __initTable( $table_key, $table_name )
	{
		$this->__tbl_key	= $table_key;
		$this->__tbl_name	= $table_name;
		$this->__tbl_alias	= $this->_createTableAlias($table_name);
		
		// init table vars
		$this->_initVars();
	}
	
	/**
	Create table alias
		@param $alias string
		@private
	**/
	private function _createTableAlias($alias)
	{
		$alias	= preg_replace('/[^a-zA-Z0-9]/', '', $alias);
		$alias	= substr($alias, 0, 6);
	
		return $alias;
	}
	
	/**
	Parse sql statement tags
		@param $sql string
		@private
	**/
	private function _parseSQL( &$sql )
	{
		$sql	= preg_replace('/<table\/>/', $this->__tbl_name, $sql);
		$sql	= preg_replace('/<alias\/>/', $this->__tbl_alias, $sql);
		$sql	= preg_replace('/<key\/>/', $this->__tbl_key, $sql);
	}
}
