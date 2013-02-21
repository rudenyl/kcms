<?php
/*
* $Id: htmlhelper.class.php, version 0.1.172011
* HTML helper base class
* @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class HTMLHelper
{
	/**
	Helper loader method
		@public
	**/
	static function _( $type )
	{
		$prefix 	= 'HelperClass';
		$file   	= '';
		$method		= $type;

		// build function calls from arguments
		$args = explode('.', $type);
		switch(count($args)) {
			case 3:
				$prefix		= preg_replace( '#[^A-Z0-9_]#i', '', $args[0] );
				$file		= preg_replace( '#[^A-Z0-9_]#i', '', $args[1] );
				$method		= preg_replace( '#[^A-Z0-9_]#i', '', $args[2] );
				break;

			case 2:
				$file		= preg_replace( '#[^A-Z0-9_]#i', '', $args[0] );
				$method		= preg_replace( '#[^A-Z0-9_]#i', '', $args[1] );
				break;
		}

		$className	= $prefix.ucfirst($file);
		if (!class_exists( $className, false )) {
			$filePath	= PATH_CLASSES .DS. 'html' .DS. strtolower($file).'.php';
			
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
			    $args[] = &$temp[$k];
			}
			
			return call_user_func_array( array( $className, $method ), $args );
		} else {
			echo $className.'::'.$method.' not supported.';
			return false;
		}
	}
}
