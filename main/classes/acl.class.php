<?php
/*
* $Id: acl.class.php
*
* ACL base class
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class ACL
{
	protected $_perms	= array();
	private $__dbo		= null;
	
	/**
	Class constructor
		@public
	**/
	function __construct()
	{
		$this->__dbo	=& Factory::getDBO();
		
		$this->_build();
	}
	
	function hasPermission( $task )
	{
		// get logged in user
		$auth		=& Factory::getAuth();
		
		if ($auth->level == 'admin') {
			return true;	// always
		}
		
		if (isset($this->_perms['user'][$task][$auth->id])) {
			return $this->_perms['user'][$task][$auth->id];
		}
		else {
			if (isset($this->_perms['role'][$task][$auth->level])) {
				return $this->_perms['role'][$task][$auth->level];
			}
		}
	
		return false;
	}
	
	private function _build()
	{
		$this->_perms	= array(
			'role' => $this->_getRolePermissions(null, 'appkey'),
			'user' => $this->_getUserPermissions(null, 'appkey')
		);
	}
	
	private function _getRolePermissions( $app='', $key='' )
	{
		$sql	= "SELECT rp.value,CONCAT(p.app,'.',p.key) as appkey,r.level,p.alias as role_alias"
		."\n FROM {TABLE_PREFIX}_role_permissions rp"
		."\n LEFT JOIN {TABLE_PREFIX}_permissions p ON p.id=rp.permission_id"
		."\n LEFT JOIN {TABLE_PREFIX}_roles r ON r.id=rp.role_id"
		.($app ? "\n WHERE p.app = " . $this->__dbo->Quote($app) : '')
		;
		$this->__dbo->query($sql);
		
		$rows	= $this->__dbo->fetch_object_list();
		
		if ($key && $rows) {
			$list	= array();
			foreach ($rows as $r) {
				if (!isset($r->{$key})) {
					continue;
				}
			
				if (!isset($list[$r->{$key}])) {
					if (!is_array(@$list[$r->{$key}])) {
						$list[$r->{$key}]	= array();
					}
				}
				
				$list[$r->{$key}][$r->level]	= $r->value;
				
				// add role permission aliases, exists
				if ($r->role_alias) {
					$perm_key		= explode('.', $r->{$key});
					$role_aliases	= explode('|', $r->role_alias);
					
					foreach($role_aliases as $alias) {
						//$perm_key[count($perm_key)-1]	= $alias;
						$perm_key_array	= array(
							$perm_key[0],
							$perm_key[1],
							$alias
						);
						
						//$key_alias	= implode('.', $perm_key);
						$key_alias	= implode('.', $perm_key_array);
						
						$list[$key_alias][$r->level]	= $r->value;
					}
				}
			}
			
			$rows	= $list;
		}
		
		return $rows;
	}
	
	private function _getUserPermissions( $app='', $key='' )
	{
		$sql	= "SELECT up.user_id,up.value,CONCAT(p.app,'.',p.key) as appkey,p.alias as role_alias"
		."\n FROM {TABLE_PREFIX}_users_permissions up"
		."\n LEFT JOIN {TABLE_PREFIX}_permissions p ON p.id=up.permission_id"
		.($app ? "\n WHERE p.app = " . $this->__dbo->Quote($app) : '')
		;
		$this->__dbo->query($sql);
		
		$rows	= $this->__dbo->fetch_object_list();
		
		if ($key && $rows) {
			$list	= array();
			foreach ($rows as $r) {
				if (!isset($r->{$key})) {
					continue;
				}
			
				if (!isset($list[$r->{$key}])) {
					if (!is_array(@$list[$r->{$key}])) {
						$list[$r->{$key}]	= array();
					}
				}
				
				$list[$r->{$key}][$r->user_id]	= $r->value;
				
				// add role permission aliases, exists
				if ($r->role_alias) {
					$perm_key		= explode('.', $r->{$key});
					$role_aliases	= explode('|', $r->role_alias);
					
					foreach($role_aliases as $alias) {
						//$perm_key[count($perm_key)-1]	= $alias;
						$perm_key_array	= array(
							$perm_key[0],
							$perm_key[1],
							$alias
						);
						
						//$key_alias	= implode('.', $perm_key);
						$key_alias	= implode('.', $perm_key_array);
						
						$list[$key_alias][$r->user_id]	= $r->value;
					}
				}
			}
			
			$rows	= $list;
		}
		
		return $rows;
	}
}
