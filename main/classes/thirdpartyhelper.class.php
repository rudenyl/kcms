<?php
/*
* $Id: thirdpartyhelper.class.php
* 3rd party library class helper
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class ThirdPartyHelper
{
	/**
	Helper loader method
		@param $type string
		@public
	**/
	static function _( $type )
	{
		$prefix 	= 'ThirdPartyClass';
		$file   	= '';
		$method		= $type;

		// build function calls from arguments
		$args = explode('.', $type);
		switch(count($args)) {
			case 2:
				$file		= preg_replace( '#[^A-Z0-9_]#i', '', $args[0] );
				$method		= preg_replace( '#[^A-Z0-9_]#i', '', $args[1] );
				break;
		}

		$className	= $prefix.ucfirst($file);
		if (!class_exists( $className, false )) {
			$filePath	= PATH_CLASSES .DS. '3rdparty' .DS. strtolower($file).'.php';
			
			if( file_exists($filePath) && !is_dir($filePath) ) {
				require_once $filePath;

				if (!class_exists( $className )) {
					echo $className.'::' .$method. ' not found in file.';
					return false;
				}
			} else {
				echo $prefix.$file . ' not supported. File not found.';
				return false;
			}
		}

		if (is_callable( array( $className, $method ) )) {
			$temp = func_get_args();
			array_shift( $temp );
			$args = array();
			foreach ($temp as $k => $v) {
			    $args[] =	$temp[$k];
			}
			
			return call_user_func_array( array( $className, $method ), $args );
		} else {
			echo $className.'::'.$method.' not supported.';
			return false;
		}
	}
}
