<?php
/*
* $Id: auth.class.php, version 0.1.05112011
* User authentication base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class Auth
{
	public $id;
	public $username;
	public $level;
	public $parent;
	public $blocked;
	
	private $_loggedIn;
	var $__app;

	/**
	Class constructor
		@public
	**/
	function __construct($user_to_impersonate = null)
	{
		// get application instance
		$this->__app		=& Factory::getApplication();
		
		$this->id			= null;
		$this->username		= null;
		$this->level		= 'guest';
		$this->parent		= null;
		$this->blocked		= null;
		$this->_loggedIn	= false;

		if (!is_null($user_to_impersonate)) {
			return $this->_impersonate($user_to_impersonate);
		}

		if ($this->attemptSessionLogin()) {
			return;
		}
	}
	
	/**
	Login function
		@param $username string
		@param $password string
		@public
	**/
	public function login( $username, $password )
	{
		$password	= $this->_createHashedPassword($password);
		
		return $this->_attemptLogin(md5($username), $password);
	}

	/**
	Logout function
		@public
	**/
	public function logout()
	{
		@session_start();
	
		$this->id			= null;
		$this->username		= null;
		$this->level		= 'guest';
		$this->parent		= null;
		$this->blocked		= null;
		$this->_loggedIn	= false;

		$__config			= $this->__app->get('config');
		$session_key		= md5($__config->salt);
		
		// clear all instances
		$_SESSION[$session_key]	= array();
		$_COOKIE[$session_key]	= array();
		
		setcookie($session_key, '', time() - 3600, '/');
		setcookie(session_name(), session_id(), 1, '/');
		
		// close session
		@session_destroy();
	}

	/**
	Re-instate user after username changes
		@param $new_username string
		@public
	**/
	public function changeUsername( $new_username )
	{
		$db		=& Factory::getDBO();
		
		$sql	= "UPDATE {TABLE_PREFIX}_users"
		."\n SET `username` = " . $db->Quote($new_username)
		."\n WHERE `id` = " . $db->Quote($this->id)
		."\n AND `blocked` = 0"
		;
		$db->query($sql);

		if ($db->affected_rows() == 1) {
			$this->_impersonate($this->id);
			
			return true;
		}

		return false;
	}

	/**
	Re-instate user after password changes
		@param $new_password string
		@public
	**/
	public function changePassword( $new_password )
	{
		$db		=& Factory::getDBO();
		
		$new_password	= $this->_createHashedPassword($new_password);
		
		$sql	= "UPDATE {TABLE_PREFIX}_users"
		."\n SET `password` = " . $db->Quote($new_password)
		."\n WHERE `id` = " . $db->Quote($this->id)
		."\n AND `blocked` = 0"
		;
		$db->query($sql);

		if ($db->affected_rows() == 1) {
			$this->_impersonate($this->id);
			
			return true;
		}

		return false;
	}

	/**
	Determine if user is logged in
		@public
	**/
	public function loggedIn()
	{
		return $this->_loggedIn;
	}

	/**
	Re-instate user 
		@param $user_to_impersonate int
		@private
	**/
	private function _impersonate( $user_to_impersonate )
	{
		$db		=& Factory::getDBO();

		$sql	= "SELECT *"
		."\n FROM {TABLE_PREFIX}_users"
		."\n WHERE " .((int)$user_to_impersonate ? $db->nameQuote('id') : $db->nameQuote('username') ). " = " . $db->Quote($user_to_impersonate)
		."\n AND `blocked` = 0"
		;
		$db->query($sql);
		$row	= $db->fetch_object();

		if (is_object($row)) {
			$this->id			= $row->id;
			$this->username		= $row->username;
			$this->level		= $row->level;
			$this->parent		= $row->parent;
			$this->blocked		= $row->blocked;

			$this->_storeSessionData(md5($this->username), $row->password);
			$this->_loggedIn	= true;

			return true;
		}

		return false;
	}

	/**
	Attempt to login using data stored in the current session or saved cookie data
		@private
	**/
	private function attemptSessionLogin()
	{
		$__config		= $this->__app->get('config');
		$session_key	= md5($__config->salt);
		
		if (isset($__config->session_type) && ($__config->session_type == 'session')) {
			@session_start();
			
			if (isset($_SESSION[$session_key]['__auth']) ) {
				@list($username, $password)	= explode('.', $_SESSION[$session_key]['__auth']);
				return $this->_attemptLogin($username, $password);
			}
		}
		else {
			// attempt from saved cookie
			if (isset($_COOKIE[$session_key]) && is_string($_COOKIE[$session_key])) {
				$cookie = json_decode($_COOKIE[$session_key], true);

				if (isset($cookie['__auth'])) {
					@list($username, $password)	= explode('.', $_SESSION[$session_key]['__auth']);
					return $this->_attemptLogin($username, $password);
				}
			}
		}

		return false;
	}

	/**
	Attempt to log credentials
		@param $username string
		@param $password string
		@private
	**/
	private function _attemptLogin( $username, $password )
	{
		$db		=& Factory::getDBO();

		$sql	= "SELECT *"
		."\n FROM {TABLE_PREFIX}_users"
		."\n WHERE md5(`username`) = " . $db->Quote($username)
		."\n AND `blocked` = 0"
		;
		$db->query($sql);
		$row	= $db->fetch_object();
		if (!$row) {
			return false;
		}
		
		if ($password != $row->password) {
			return false;
		}

		// update last login date
		if ($this->_in_session($username, $password)) {
			$now	= date('Y-m-d H:i:s');
			$sql	= "UPDATE {TABLE_PREFIX}_users"
			."\n SET `lastvisit` = " . $db->Quote($now)
			."\n WHERE `id` = " . $db->Quote($row->id)
			;
			$db->query($sql);
		}

		$this->id		= $row->id;
		$this->username	= $row->username;
		$this->level	= $row->level;
		$this->parent	= $row->parent;
		$this->blocked	= $row->blocked;
		
		$this->_storeSessionData($username, $password);
		$this->_loggedIn	= true;

		return true;
	}

	/**
	Store session data
		@param $username string
		@param $password string
		@private
	**/
	private function _storeSessionData( $username, $password )
	{
		if (headers_sent()) {
			return false;
		}
		
		// create session key
		$__config		= $this->__app->get('config');
		$session_key	= md5($__config->salt);
		$auth_key		= $username .'.'. $password;
		
		// add to session
		@session_start();
		$_SESSION[$session_key]['__auth']	= $auth_key;
		// add to cookie
		$cookie		= json_encode(array('__auth' => $auth_key));
		
		// get session lifetime (minutes)
		$session_time	= isset($__config->session_time) ? $__config->session_time : 1;
		$session_time	= (int)$session_time ? $session_time : 1;
		
		return setcookie($session_key, $cookie, time() + 60 * $session_time, '/');
	}

	/**
	Validate if username and/or password is active
		@param $username string
		@param $password string
		@private
	**/
	private function _in_session( $username, $password )
	{
		$__config		= $this->__app->get('config');
		$session_key	= md5($__config->salt);
		$auth_key		= $username .'.'. $password;
		
		if (isset($__config->session_type) && ($__config->session_type == 'session')) {
			@session_start();
			
			if (isset($_SESSION[$session_key]['__auth'])) {
				return (($_SESSION[$session_key]['__auth'] == $auth_key));
			}
		}
		else {
			// attempt from saved cookie
			if (isset($_COOKIE[$session_key]) && is_string($_COOKIE[$session_key])) {
				$cookie = json_decode($_COOKIE[$session_key], true);

				if (isset($cookie['__auth'])) {
					return (($cookie['__auth'] == $auth_key));
				}
			}
		}

		return false;
	}

	/**
	Create a sha1-hashed password
		@param $password string
		@private
	**/
	private function _createHashedPassword( $password )
	{
		$__config	= $this->__app->get('config');
		return sha1($password . $__config->salt);
	}
}