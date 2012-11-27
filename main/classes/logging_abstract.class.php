<?php
/*
* $Id: logging_abstract.class.php
*
* Logging system abstract class
*/

defined('_PRIVATE') or die('Direct access not allowed');

abstract class Logging_Abstract
{
	abstract function store( $msg, $date=null );
	abstract function getLogFile();
}
