<?php
/*
* $Id: sqlite.udf.php, version 1.0
*
*/

defined('_PRIVATE') or die('Direct access not allowed');

/**
* String functions
*/
function sqlite__CONCAT()
{
	$args	= func_get_args();
	$str	= '';
	
	foreach ($args as $arg) {
		$str	.= $arg;
	}
	
	return $str;
}

// http://stackoverflow.com/questions/6885793/php-equivalent-of-mysqls-function-substring-index
function sqlite__SUBSTRING_INDEX( $str, $delim, $count )
{
	if ($count < 0) {
		return implode($delim, array_slice(explode($delim, $str), $count));
	}
	else {
		return implode($delim, array_slice(explode($delim, $str), 0, $count));
	}
}
/**
* Conditional statements
*/
function sqlite__IF( $statement, $res1, $res2 )
{
    if ($statement) {
        return $res1;
    } 
	else {
        return $res2;
    }
}

function sqlite__IFNULL( $res1, $res2 )
{
    if (empty($res1)) {
        return $res2;
    } 
	else {
        return $res1;
    }
}

/**
* Date functions
*/
function sqlite__DATEDIFF( $ts1, $ts2 )
{
	return @strtotime($ts1) - @strtotime($ts2);
}

function sqlite__YEAR( $ts )
{
	return date('Y', strtotime($ts));
}

function sqlite__MONTH( $ts )
{
	return date('n', strtotime($ts));
}

function sqlite__DAY( $ts )
{
	return date('d', strtotime($ts));
}

function sqlite__NOW()
{
	return strtotime('now');
}

function sqlite__DATE_FORMAT( $ts, $format )
{
	return strftime($format, $ts);
}

function sqlite__LAST_DAY( $ts )
{
	return date('Y-m-d', strtotime(date('Y-m-01', strtotime("{$ts} +1 MONTH")) . ' -1 SECOND'));
}
