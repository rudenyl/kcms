<?php
/* 
 * $Id: tablelist.php
 * HTMLHelper plugin class
 * @author: Dhens <rudenyl@gmail.com>
*/
 
class HelperClassTableList
{
	static function sort( $text, $column_name, $direction=null, $current_column=null, $rows=null )
	{
		$column		= new stdclass();
		$attribute	= '';
	
		// get active
		if( $current_column ) {
			if( ($column_name == $current_column) && $direction ) {
				$attribute	= "-{$direction} active";
			}
		}
		// set next direction
		if( $direction === null ) {
			$column->dir	= 'asc';
		} else {
			$column->dir	= ($direction == 'asc' ? 'desc' : 'asc');
		}
		
		$column->column	= $column_name;
		$dir			= json_encode($column);
		
		$html	= "<a href=\"#\" class=\"table-column-sort{$attribute}\" rel='{$dir}'><span>{$text}</span></a>";
		
		return $html;
	}
}
