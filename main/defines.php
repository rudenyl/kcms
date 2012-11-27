<?php
/**
 * $Id: framework.php
 * Constants definitions
 * @author: Dhens <rudenyl@gmail.com>
*/

defined('_PRIVATE') or die('Direct access not allowed');

define('DS', DIRECTORY_SEPARATOR);

$thisPath	= dirname(__FILE__);
if ( !defined('BASE_PATH') ) {
	$path	= dirname($thisPath);
	
	define('BASE_PATH', $path);
}

// application path
define('PATH_APPLICATIONS',	BASE_PATH .DS. 'applications');
define('PATH_LIBRARIES',	BASE_PATH .DS. 'includes');
define('PATH_CLASSES',		BASE_PATH .DS. 'includes' .DS. 'classes');
define('PATH_MODULES',		BASE_PATH .DS. 'modules');
define('PATH_TEMPLATES',	BASE_PATH .DS. 'templates');
define('PATH_CACHE', 		BASE_PATH .DS. '_cache');
