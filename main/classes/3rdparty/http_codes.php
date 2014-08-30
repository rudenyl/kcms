<?php
defined('_PRIVATE') or die('Direct access not allowed');

class ThirdPartyClassHTTP_Codes
{
	function _( $err_code )
	{
		$http_codes		= array();
		
		$http_code_file	= dirname(__FILE__) .DS. 'http_codes.txt';
		if (is_file($http_code_file)) {
			$http_codes	= Utility::parse_ini_file($http_code_file);
		}
		
		if (empty($http_codes)) {
			return '?';
		}
		else {
			if (isset($http_codes[$err_code])) {
				return $http_codes[$err_code];
			}
			
			return "{$err_code}: Unknown HTTP code.";
		}
	}
}